<?php
declare(strict_types=1);
namespace Kidstell\Bitlady\Tests\Unit\Configurations;

use PHPUnit\Framework\TestCase;
use Kidstell\Bitlady\BitladyTrait;

final class MultiBlockTest extends TestCase
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
    }

    public function testGetDefaultUseSeparator(){
        $reflection = new \ReflectionClass(get_class($this->classUseDefaults));
        $refDefaultMultiBlock = $reflection->getStaticPropertyValue('defaultBitladyUseMultipleBlocks');

        $class1 = new $this->classUseDefaults;
        $defaultMultiBlock = $class1::bitladyUseMultipleBlocks();

        $this->assertSame($defaultMultiBlock,$refDefaultMultiBlock,"BitladyTrait class must return defaultBitladyUseMultipleBlocks when the parent class has not defined necessary static variables");
    }
    
    public function testMultiBlockSetToNull()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refDefaultMultiBlock = $reflection->getStaticPropertyValue('defaultBitladyUseMultipleBlocks');

        $class1 = new $this->classUseNullProps;
        $defaultMultiBlock = $class1::bitladyUseMultipleBlocks();

        $this->assertSame($refDefaultMultiBlock,$defaultMultiBlock,"When parent::\$bitladyUseMultipleBlocks is null, parent::getBitladyUseSeparator() should return same as BitladyTrait::defaultBitladyUseMultipleBlocks");
    }

    public function testOverrideDefaultUseSeps()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refDefaultMultiBlock = $reflection->getStaticPropertyValue('defaultBitladyUseMultipleBlocks');
        $refMulti = $reflection->getProperty('bitladyUseMultipleBlocks'); //null value expected
        $refMultivalue = $refMulti->getValue(); //null value expected
        $refMulti->setValue($refDefaultMultiBlock);


        $class1 = new $this->classUseNullProps;
        $newRefMulti = $class1::bitladyUseMultipleBlocks();
        $this->assertSame($refDefaultMultiBlock,$newRefMulti,"When parent::bitladyUseMultipleBlocks is set, BitladyTrait::defaultBitladyUseSeparator must be overriden");

        $refMulti->setValue(!$refDefaultMultiBlock);
        $newRefMulti = $class1::bitladyUseMultipleBlocks();
        $this->assertNotSame($refDefaultMultiBlock,$newRefMulti,"When parent::bitladyUseMultipleBlocks is set, BitladyTrait::defaultBitladyUseSeparator must be overriden");
    }

    public function testUpdateBitOffRangeException()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refDefaultMultiBlock = $reflection->getStaticPropertyValue('defaultBitladyUseMultipleBlocks');
        $refMulti = $reflection->getProperty('bitladyUseMultipleBlocks'); //null value expected
        $refMulti->setValue(false);

        $class1 = new $this->classUseNullProps;
        $class1->permStatus = '0';
        $limit = $class1::getBitladyBase();

        $this->expectException('Exception');
        $this->expectExceptionMessageMatches("/which you tried to modify is OutOfRange, You may enable 'bitladyUseMultipleBlocks', but this may break existing data already stored on DB\$/");
        $propState = $class1->setPropertyState('permStatus',$limit,true);
    }


    public function testToggleBitOffRangeException()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refDefaultMultiBlock = $reflection->getStaticPropertyValue('defaultBitladyUseMultipleBlocks');
        $refMulti = $reflection->getProperty('bitladyUseMultipleBlocks'); //null value expected
        $refMulti->setValue(false);

        $class1 = new $this->classUseNullProps;
        $class1->permStatus = '0';
        $limit = $class1::getBitladyBase();

        $this->expectException('Exception');
        $this->expectExceptionMessageMatches("/which you tried to modify is OutOfRange, You may enable 'bitladyUseMultipleBlocks', but this may break existing data already stored on DB\$/");
        $propState = $class1->togglePropertyState('permStatus',$limit);
    }

}