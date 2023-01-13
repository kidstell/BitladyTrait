<?php

use PHPUnit\Framework\TestCase;

use Kidstell\Bitlady\BitladyCore;
use Kidstell\Bitlady\BitladyTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Kidstell\Bitlady\Tests\Classes\LaravelApp;
use Kidstell\Bitlady\Tests\Classes\LaravelShop;

final class LaravelFeaturesTest extends TestCase{
    protected function setUp():void
    {
        parent::setUp();
        $this->app = (new LaravelApp())->init([LaravelShop::class]);
        $this->classUseDefaults = new class() {
            use BitladyTrait;
        };
        $this->classUseNullProps = new class() {
            use BitladyTrait;
            protected static $bitladyBase;
            protected static $bitladyUseSeparator;
            protected static $bitladyUseMultipleBlocks;
        };

        $this->classUseMagic = new class() extends Model {
            use BitladyTrait;

            protected static $bitladyWatcher;
            public $triggerException = false;

            function __get($name)
            {
                $prefix = strtolower(substr($name,0,5));
                $rest = lcfirst(substr($name,5));

                if($prefix == 'magic' && isset($this->$rest)) return $this->$rest;
                throw new \Exception("Variable $name not defined", 1);
            }

            public function __call($method, $parameters)
            {
                $res = $this->bitladyMagicAction($method,$parameters,$this->triggerException);
                if ($res !== -1 && !is_null($res)) return $res;
                
                if(get_parent_class($this) !== false)
                    return call_user_func(['parent',$method],...$parameters);
        
                if($this->triggerException)
                    throw new \Exception("Method not found; Method name: ".$method, 1);
            }
        };

    }

    // function to test db connectivity
    public function testModelConnectivity()
    {
        $seedCount = LaravelShop::count();//->get();//
        $this->assertGreaterThan(0,$seedCount,'If DB connect successfully and seeeding was properly done, seed count should be greater than zero');
    }


    // function to test normal getPropertyState and setPropertyState
    public function testBitSettersAndGetters()
    {
        $shops = LaravelShop::all();
        $base = LaravelShop::getBitladyBase();
        $passed1 = true;
        $passed2 = true;
        foreach ($shops as $key => $shop) {
            $thisPerms = $shop->perms;
            $blocks = BitladyCore::_blTransformStatesToLevels($thisPerms,$base);
            $blocksBinReverse = $blocksBin = array_map(function($item) use ($base){
                return str_pad(decbin($item),$base,'0',STR_PAD_LEFT);
            },$blocks);
            
            krsort($blocksBinReverse);
            $blocksBinStrZero = strrev(implode('',$blocksBinReverse));

            $retrievedPerms = $shop->loadPermsBit(LaravelShop::$permsList);

            foreach (LaravelShop::$permsList as $propertyId) {
                if (!isset($retrievedPerms[$propertyId]) || $retrievedPerms[$propertyId] !== (bool)substr($blocksBinStrZero,$propertyId,1) || $shop->getPropertyState('perms',$propertyId)!== $retrievedPerms[$propertyId] || $shop->getPermsBit($propertyId)!== $retrievedPerms[$propertyId]) {
                    $passed1 = false;
                    break 2;
                }
            }
            $hex2int = $shop->otherUsersPerms;
            if ($hex2int !== $shop->perms) {
                $passed2 = false;
                break;
            }
        }
        $this->assertTrue($passed1,'failed to correctly retrieve bits from laravel using loadBitsToArray() or getPropertyState or getPermsBits');
        $this->assertTrue($passed2,'failed to correctly handle hexadecimal values of bits using Laravel\'s accessors and mutators');

        $passed3 = true;
        foreach ($shops as $key => $shop) {
            $thisPerms = $shop->perms;
            $blocks = BitladyCore::_blTransformStatesToLevels($thisPerms,$base);
            $blocksBinReverse = $blocksBin = array_map(function($item) use ($base){
                return str_pad(decbin($item),$base,'0',STR_PAD_LEFT);
            },$blocks);
            
            krsort($blocksBinReverse);
            $blocksBinStrZero = strrev(implode('',$blocksBinReverse));

            $retrievedPerms = $shop->loadPermsBit(LaravelShop::$permsList);

            foreach (LaravelShop::$permsList as $propertyId) {
                $shop->togglePermsBit($propertyId);
                $shop->toggleOtherUsersPermsBit($propertyId);
            }
            $hex2int = $shop->otherUsersPerms;
            if ($hex2int !== $shop->perms) {
                $passed3 = false;
                break;
            }
        }
        $this->assertTrue($passed3,'bitlady\'s magic toogle failed to yield same result for normal variables and laravel\'s mutated variables');
    }

    public function testLaravelQueryScope()
    {
        $shops = LaravelShop::all();
        $passed1 = true;
        $passed2 = true;

        foreach (LaravelShop::$permsList as $propertyId) {
            $shopsWhereBitSet = LaravelShop::whereBitSet('perms',$propertyId,true)->get()->pluck('id');
            
            $tracking = $shops->filter(function ($shop, $key) use ($propertyId) {
                return $shop->getPermsBit($propertyId);
            })->pluck('id');

            $check = count(($shopsWhereBitSet->diff($tracking))->all());

            if ($check > 0) {
                $passed1 = false;
                break;
            }

            $shopsWhereBitNotSet = LaravelShop::whereBitNotSet('perms',$propertyId)->get()->pluck('id');
            
            $tracking = $shops->filter(function ($shop, $key) use ($propertyId) {
                return !$shop->getPermsBit($propertyId);
            })->pluck('id');

            $check = count(($shopsWhereBitNotSet->diff($tracking))->all());

            if ($check > 0) {
                $passed2 = false;
                break;
            }
        }
        
        $this->assertTrue($passed1,"The scope Query (whereBitSet) did not yield same result as the bitlady functions");
        $this->assertTrue($passed2,"The scope Query (whereBitNotSet) did not yield same result as the bitlady functions");
    }

    public function testSingleBlockQueryScope(){
        $mall = new Class () extends LaravelShop {
            use BitladyTrait;

            protected $table = 'laravel_shops';
            public static $bitladyUseMultipleBlocks = false;
            public static $bitladyBase = 64;
            
            protected static function booted()
            {
                static::addGlobalScope('ancient', function (Builder $builder) {
                    $builder->where('id', '>', 8);
                });
            }
        };

        $mall->seed(20);

            

        $shops = $mall::all();
        $passed1 = true;
        $passed2 = true;

        foreach ($mall::$permsList as $propertyId) {
            $shopsWhereBitSet = $mall::whereBitSet('perms',$propertyId,true)->get()->pluck('id');
            
            $tracking = $shops->filter(function ($shop, $key) use ($propertyId) {
                return $shop->getPermsBit($propertyId);
            })->pluck('id');

            $check = count(($shopsWhereBitSet->diff($tracking))->all());

            if ($check > 0) {
                $passed1 = false;
                break;
            }

            $shopsWhereBitNotSet = $mall::whereBitNotSet('perms',$propertyId)->get()->pluck('id');
            
            $tracking = $shops->filter(function ($shop, $key) use ($propertyId) {
                return !$shop->getPermsBit($propertyId);
            })->pluck('id');

            $check = count(($shopsWhereBitNotSet->diff($tracking))->all());

            if ($check > 0) {
                $passed2 = false;
                break;
            }
        }
        
        $this->assertTrue($passed1,"The scope Query (whereBitSet) did not yield same result as the bitlady functions");
        $this->assertTrue($passed2,"The scope Query (whereBitNotSet) did not yield same result as the bitlady functions");
    }
}