<?php
declare(strict_types=1);
namespace Kidstell\Bitlady\Tests\Unit\Configurations;

use PHPUnit\Framework\TestCase;
use Kidstell\Bitlady\BitladyTrait;

final class BitBlockSeparatorTest extends TestCase
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
        // $reflection = new \ReflectionClass(ClassUseDefaults::class);
        $reflection = new \ReflectionClass(get_class($this->classUseDefaults));
        $refDefaultUseSep = $reflection->getStaticPropertyValue('defaultBitladyUseSeparator');

        // $class1 = new ClassUseDefaults;
        $class1 = new $this->classUseDefaults;
        $defaultUseSep = $class1::getBitladyUseSeparator();

        $this->assertSame($defaultUseSep,$refDefaultUseSep,"BitladyTrait class must return defaultBitladyUseSeparator when the parent class has not defined necessary static variables");
    }
    
    public function testUseSeparatorSetToNull()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refDefaultUseSeps = $reflection->getStaticPropertyValue('defaultBitladyUseSeparator');

        $class1 = new $this->classUseNullProps;
        $defaultUseSeps = $class1::getBitladyUseSeparator();

        $this->assertSame($refDefaultUseSeps,$defaultUseSeps,"When parent::\$bitladyUseSeparator is null, parent::getBitladyUseSeparator() should return same as BitladyTrait::defaultBitladyUseSeparator");
    }

    public function testOverrideDefaultUseSeps()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refDefaultBitladyBase = $reflection->getStaticPropertyValue('defaultBitladyUseSeparator');
        $refBase = $reflection->getProperty('bitladyUseSeparator'); //null value expected
        $refBasevalue = $refBase->getValue(); //null value expected
        $refBase->setValue($refDefaultBitladyBase);

        $class1 = new $this->classUseNullProps;
        $newBase = $class1::getBitladyUseSeparator();
        $this->assertTrue($newBase,"When parent::bitladyUseSeparator is set, BitladyTrait::defaultBitladyUseSeparator must be overriden");

        $refBase->setValue(!$refDefaultBitladyBase);
        $newBase = $class1::getBitladyUseSeparator();
        $this->assertFalse($newBase,"When parent::bitladyUseSeparator is set, BitladyTrait::defaultBitladyUseSeparator must be overriden");
    }

    public function testPrintSeparator()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refSep = $reflection->getProperty('bitladyUseSeparator'); //null value expected
        $refSep->setValue(true); 
        $refMulti = $reflection->getProperty('bitladyUseMultipleBlocks'); 
        $refMulti->setValue(true); 
        $refBase = $reflection->getProperty('bitladyBase'); 
        $refBase->setValue(2); 

        $class1 = new $this->classUseNullProps;
        $class1->properties = '0';

        $state = false;
        foreach ([0,1,2,3,4,5,6,7,8,9,10] as $value) {
            $class1->setPropertyState('properties', $value, $state);
            $state = !$state;
        }

        $this->assertStringContainsString('|',$class1->properties,"Separators must be used when enabled, to separate multiple blocks of bits");
        $this->assertEquals(5,substr_count($class1->properties,'|'),"Separator must demarcate appropriately by bitBase");
    }

}