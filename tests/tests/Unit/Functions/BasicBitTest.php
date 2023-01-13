<?php
declare(strict_types=1);
namespace Kidstell\Bitlady\Tests\Unit\Functions;

use PHPUnit\Framework\TestCase;
use Kidstell\Bitlady\BitladyTrait;
use Kidstell\Bitlady\Tests\Classes\Util;

final class BasicBitTest extends TestCase
{

    protected function setUp():void
    {
        parent::setUp();
        $this->classUseDefaults = new class() {
            use BitladyTrait;

            // public $permissionBits='0';
        };
        $this->classUseNullProps = new class() {
            use BitladyTrait;
            protected static $bitladyBase;
            protected static $bitladyUseSeparator;
            protected static $bitladyUseMultipleBlocks;
        };
    }

    public function testSetAndGetBit()
    {
        $class1 = new $this->classUseDefaults;
        $class1->permissionBits = '0';
        $base = $class1::getBitladyBase();

        $propertyId = rand(0,$class1::getBitladyBase()-1);
        
        $class1->setPropertyState('permissionBits', $propertyId,true);
        $retrieved = $class1->getPropertyState('permissionBits', $propertyId);
        
        $this->assertSame(true,$retrieved,"The retrieved Value must be same as the value that was set");

        $bin = decbin((int)$class1->permissionBits);

        $this->assertSame(substr_count($bin,'1'),1,"Only one bit is expected to be set");

        $propertyId = ($propertyId+5)%($base-1);
        $class1->setPropertyState('permissionBits', $propertyId,true);
        $retrieved = $class1->getPropertyState('permissionBits', $propertyId);
        $this->assertSame(true,$retrieved,"The retrieved Value must be same as the value that was set");
        $bin = decbin((int)$class1->permissionBits);

        $this->assertSame(substr_count($bin,'1'),2,"Setting a new Bit must not destroy the existing bits");
    }

    public function testToggleAndGetBit()
    {
        $class1 = new $this->classUseDefaults;
        $class1->permissionBits = '1';
        $base = $class1::getBitladyBase();

        $propertyId = $class1::getBitladyBase()-2;
        
        $initial = $class1->getPropertyState('permissionBits', $propertyId);
        $class1->togglePropertyState('permissionBits', $propertyId);
        $retrieved1 = $class1->getPropertyState('permissionBits', $propertyId);
        $class1->togglePropertyState('permissionBits', $propertyId);
        $retrieved2 = $class1->getPropertyState('permissionBits', $propertyId);
        $class1->togglePropertyState('permissionBits', $propertyId);
        $retrieved3 = $class1->getPropertyState('permissionBits', $propertyId);

        $setBits = substr_count(decbin((int)$class1->permissionBits),'1');
        
        $this->assertFalse($initial,"False must be returned when fetching a bit that has not been set");
        $this->assertNotSame($initial,$retrieved1,"Bit's Value must change when toggled");
        $this->assertTrue($retrieved1,"When False is toggled, True must be returned");
        $this->assertNotSame($retrieved2,$retrieved1,"Multiple toggling should consistently produce alternating values");
        $this->assertSame($retrieved1,$retrieved3,"if True is returned after toggling, the True should be same whether the bit was initialised or not");
        $this->assertSame($setBits,2,"Even after multiple toggling, only targetted bits must be updated");
    }

    public function testLoadAndGetBit()
    {
        $orderApp = new class{
            use BitladyTrait;

            const DISPATCH_COMPLETED = 60;
            const BUYER_CONFIRM_DISPATCH = 50;
            const DISPATCH_IN_TRANSIT = 40;
            const ORDER_AVAILABILITY_CHECK = 30;
            const ORDER_PAYMENT_RECEIVED = 20;
            const ORDER_CREATED = 10;
            const ORDER_COMPLETED = 0;

            public $possibleActions = [
                self::DISPATCH_COMPLETED,
                self::BUYER_CONFIRM_DISPATCH,
                self::DISPATCH_IN_TRANSIT,
                self::ORDER_AVAILABILITY_CHECK,
                self::ORDER_PAYMENT_RECEIVED,
                self::ORDER_CREATED,
                self::ORDER_COMPLETED
            ];

            public $actionStates = '0';
            public $triggerException = true;
            public static $bitladyUseMultipleBlocks = true;

            function __construct()
            {
                foreach ($this->possibleActions as $value) {
                    $this->setActionStatesBit($value, (bool)Util::randomBits(1));
                }
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

        $order = new $orderApp();
        $actionStatesAsArray = $order->loadBitsToArray('actionStates',$order->possibleActions,false);
        $actionStatesAsArray2 = $order->loadActionStatesBit($order->possibleActions,false);

        $result = true;
        foreach ($order->possibleActions as $propertyId) {
            $currValue = $order->getActionStatesBit($propertyId);
            if($currValue != $actionStatesAsArray[$propertyId]){
                $result = false; break;
            }
        }

        $this->assertTrue($result,"The loadBitsToArray function must be consistent with the getPropertyState function");

        $copyActionStates = $order->actionStates;
        $order->actionStates = '0';

        $retrievedStates = $order->stringifyBitsArray('actionStates',$actionStatesAsArray);
        $retrievedStates2 = $order->stringifyActionStatesBit($actionStatesAsArray);

        $this->assertSame($actionStatesAsArray,$actionStatesAsArray2, "Magic load function must produce same values as loadBitsToArray function");
        $this->assertSame($copyActionStates,$retrievedStates, "stringifyBitsArray must be able to re-create any state given the cuorresponding array");
        $this->assertSame($retrievedStates,$retrievedStates2, "Bitlady's magic stringify method must mimic stringifyBitsArray's function exactly");

    }


}