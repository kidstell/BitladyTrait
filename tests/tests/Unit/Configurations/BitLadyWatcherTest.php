<?php
declare(strict_types=1);
namespace Kidstell\Bitlady\Tests\Unit\Configurations;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use Kidstell\Bitlady\BitladyTrait;
use Illuminate\Database\Eloquent\Model;
use Kidstell\Bitlady\BitladyCore;
use Kidstell\Bitlady\Tests\Classes\Util;

final class BitLadyWatcherTest extends TestCase
{
    protected function setUp():void
    {
        parent::setUp();
        $this->classUseDefaults = new class() {
            use BitladyTrait;
        };
        $this->classUseNullProps = new class() {
            use BitladyTrait;
            protected static $bitladyBase;
            protected static $bitladyUseSeparator;
            protected static $bitladyUseMultipleBlocks;
        };

        $this->classUseMagicGet = new class() {
            use BitladyTrait;

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

        $this->classUseWatcher = new class() extends Model {
            use BitladyTrait;
            protected static $bitladyBase;
            protected static $bitladyUseSeparator;
            protected static $bitladyUseMultipleBlocks;

            protected static $bitladyWatcher;

            public function __call($method, $parameters)
            {
                if ($this->checkBitladyMagicWatch($method,$parameters)) {
                    return $this->bitladyMagicAction($method,$parameters);
                }
                
                if(get_parent_class($this) !== false)
                    return call_user_func(['parent',$method],...$parameters);
                
                if($this->triggerException)
                    throw new \Exception("Method not found; Method name: ".$method, 1);
            }

        };
    }

    public function testBitLadyWatcher()
    {
        $watcherSample = [
            'afroPop',
            'candyCon',
            'aliasToCandyCon' => 'candyCon'
        ];

        $reflection = new ReflectionClass(get_class($this->classUseWatcher));
        $refWatcher = $reflection->getProperty('bitladyWatcher'); //null value expected
        $refWatcherValue = $refWatcher->getValue(); //null value expected
        $refWatcher->setValue($watcherSample);

        $class1 = new $this->classUseWatcher;
        $base = 8;//$class1::getBitladyBase();
        $randomSet = (string)Util::randomBits($base);
        $class1->permsCacheHolder = (string)bindec($randomSet);
        $class1->candyCon = (string)bindec($randomSet);

        $class2 = new $this->classUseMagicGet;
        $class2->snapDragonLord = (string)bindec($randomSet);
        
        $result1 = $result2 = $result3 = true;
        for ($i=$base-1; $i >= 0; $i--) { 

            $expected = (bool)substr($randomSet,-($i+1),1);

            $actual1 = $class1->getPermsCacheHolderBit($i);
            if ($actual1 !== $expected) {
                $result1 = false;
                break;
            }

            $actual2 = $class1->getAliasToCandyConBit($i);
            if ($actual2 !== $expected) {
                $result2 = false;
                break;
            }

            $actual3 = $class2->getMagicSnapDragonLordBit($i);
            if ($actual3 !== $expected) {
                $result3 = false;
                break;
            }
        }

        $this->assertTrue($result1,"Bitlady's magic getter must return accurately whatever was stored in the State variable");
        $this->assertTrue($result2,"Bitlady's magic getter must use the alias stored in \$this->bitladyWatcher to return accurately whatever was stored in the State variable");
        $this->assertTrue($result3,"Bitlady's magic getter must use the magic __get function to return accurately whatever was stored in the State variable");

        $table = $class1->getTable();
        $table2 = BitladyCore::camelToSnake(basename(__FILE__));
        $this->assertStringContainsString($table2,$table,"when bitlady can not handle a magic call, classUseWatcher::__call() must be able to access parent::__call(). Failure reason: Model as a prent may have changed, or the classUseWatcher::__call may be faulty");
    }

    public function testMagicFuncWithoutPropertyIdErr()
    {
        $base = 8;//$class1::getBitladyBase();
        $randomSet = (string)Util::randomBits($base);

        $class2 = new $this->classUseMagicGet;
        $class2->snapDragonLord = (string)bindec($randomSet);
        $class2->triggerException = false;
        $class2->getMagicSnapDragonLordBit();
        
        $class2->triggerException = true;
        $this->expectException('Exception');
        $this->expectExceptionMessageMatches("/No property ID was defined in the method called. Method:/");
        $class2->getMagicSnapDragonLordBit();

    }

    public function testInvalidMagicFuncErr()
    {
        $class2 = new $this->classUseMagicGet;
        $base = 8;//$class1::getBitladyBase();
        $randomSet = (string)Util::randomBits($base);
        $class2->snapDragonLord = (string)bindec($randomSet);

        $class2->triggerException = false;
        $q = $class2->getMagicSnapDragonLordssssssBit();

        $class2->triggerException = true;
        $this->expectException('Exception');
        $this->expectExceptionMessageMatches("/could not determine the variable name from the method called. Method: /");
        $class2->getMagicSnapDragonLordssssssBit();
    }

    public function testMagicToggleFunc()
    {
        $class1 = new $this->classUseMagicGet;
        $base = 32;
        $randomSet = (string)Util::randomBits($base);
        $invertedSet = '';

        $class1->flagSet10 = (string)bindec($randomSet);
        $class1->flagSet11 = (string)bindec($randomSet);
        $class1->flagSet20 = '0';

        for ($i=$base-1; $i >= 0; $i--) { 

            $expected = $class1->getFlagSet10Bit($i);
            $invertedSet = $invertedSet.((int)!$expected);

            $class1->toggleFlagSet11Bit($i);

            $class1->setFlagSet20Bit($i,$expected);
        }

        $invertedSet  = bindec($invertedSet) + 0;
        $class1->flagSet10 += 0;
        $class1->flagSet11 += 0;
        $class1->flagSet20 += 0;


        $this->assertSame($class1->flagSet10,$class1->flagSet20, "Set bit via magic function must be consistent");
        $this->assertSame($class1->flagSet11,$invertedSet,"toggle bit via magic function must be consistent");
    }
}