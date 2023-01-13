<?php
namespace Kidstell\Bitlady\Tests\Classes;

use Kidstell\Bitlady\BitladyCore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Kidstell\Bitlady\Tests\Classes\MigrateableInterface;
use Kidstell\Bitlady\LaravelBitladyTrait;

class LaravelShop extends Model implements MigrateableInterface
{
    use LaravelBitladyTrait;

    const CAN_CLOSE_SHOP = 35;
    const CAN_OPEN_SHOP = 30;
    const CAN_SELL_SHOP = 32;
    const CAN_SELL_PRODUCTS = 34;
    const CAN_BUY_PRODUCTS = 31;
    const CAN_MODIFY_INVENTORY = 62;
    const CAN_PRINT_MONEY = 12;
    const CAN_MINT_MONEY = 10;

    public static $permsList = [
        LaravelShop::CAN_CLOSE_SHOP,
        LaravelShop::CAN_OPEN_SHOP,
        LaravelShop::CAN_SELL_SHOP,
        LaravelShop::CAN_SELL_PRODUCTS,
        LaravelShop::CAN_BUY_PRODUCTS,
        LaravelShop::CAN_MODIFY_INVENTORY,
        LaravelShop::CAN_PRINT_MONEY,
        LaravelShop::CAN_MINT_MONEY,
    ];

    protected $guarded = [];
    public $timestamps = false;
    private $_seeded = false;
    private $triggerException = false;

    public $bitladyUseSeparator = false;
    public static $bitladyUseMultipleBlocks = true;

    public function __call($method, $parameters)
    {
        $res = $this->bitladyMagicAction($method,$parameters,$this->triggerException);
        if ($res !== -1 && !is_null($res)) return $res;
        
        if(get_parent_class($this) !== false)
            return call_user_func(['parent',$method],...$parameters);
        
        if($this->triggerException)
            throw new \Exception("Method not found; Method name: ".$method, 1);
    }

    public function getOtherUsersPermsAttribute($value)
    {        
        return BitladyCore::_blMergeBlocks(array_map(function($item){
            return hexdec($item);
        },explode('|',$value)),$this->getBitladyBase(),$this->getBitladyUseSeparator());
    }

    public function setOtherUsersPermsAttribute($value)
    {
        $this->attributes['otherUsersPerms'] = implode("|",array_map(function($item){
            return dechex($item);
        },explode('|',$value)));
    }
    protected static function booted()
    {
        static::addGlobalScope('ancient', function (Builder $builder) {
            $builder->where('id', '<', 9);
        });
    }
    
    public function dbUp()
    {
        Capsule::schema()->create($this->getTable(), function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('perms')->nullable();
            $table->string('otherUsersPerms')->nullable();
        });
    }

    public function seed(int $seedCount=8)
    {
        if ($this->_seeded) return;

        $seedCount = min(15,$seedCount);

        $this->triggerException = false;
        for ($i=0; $i < $seedCount; $i++) { 
            $shop = static::create();
            $shop->name = 'Alphara'.$i;
            $shop->perms = '0';
            foreach (self::$permsList as $permId) {
                $randValue = (bool)array_rand([false,true]);
                if (($i===0 || $randValue) && $permId == static::CAN_MODIFY_INVENTORY) {
                    $randValue = true;
                }
                $shop->setPermsBit($permId,$randValue);
            }
            $shop->otherUsersPerms = $shop->perms;
            $shop->save();
        }

        $this->_seeded = true;
    }

    public function dbDown()
    {
        Capsule::schema()->drop($this->getTable());
    }
}