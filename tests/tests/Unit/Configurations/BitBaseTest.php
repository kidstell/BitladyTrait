<?php
declare(strict_types=1);
namespace Kidstell\Bitlady\Tests\Unit\Configurations;

use PHPUnit\Framework\TestCase;
use Kidstell\Bitlady\BitladyTrait;

final class BitBaseTest extends TestCase
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

    /**
     * @desc: confirm that $this->getBitladyBase() would yield value of BitladyTrait::defaultBitladyBase when it
     *        is not overriden by $this->bitladyBase
     */
    public function testGetDefaultBitladyBase()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseDefaults));
        $refDefaultBitladyBase = $reflection->getStaticPropertyValue('defaultBitladyBase');

        $class1 = new $this->classUseDefaults;
        $defaultBase = $class1::getBitladyBase();

        $this->assertSame($defaultBase,$refDefaultBitladyBase,"BitladyTrait class must return default bitladyBitBase when the parent class has not defined necessary static variables");
    }

    /**
     * @desc: confirm that $this->getBitladyBase() would yield value of BitladyTrait::defaultBitladyBase when it
     *        is not overriden by $this->bitladyBase. even if $this->bitladyBase is declared or set to NULL
     */
    public function testBitladyBaseSetToNull()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refDefaultBitladyBase = $reflection->getStaticPropertyValue('defaultBitladyBase');

        $class1 = new $this->classUseNullProps;
        $defaultBase = $class1::getBitladyBase();

        $this->assertSame($refDefaultBitladyBase,$defaultBase,"When parent sets bitladyBase to null, parent::getBitladyBase() should return same as BitladyTrait::defaultBitladyBase");
    }

    /**
     * @desc: confirm that $this->getBitladyBase() would yield value of $this->bitladyBase, given that $this->bitladyBase has a valid value 
     */
    public function testOverrideDefaultBitBase()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refDefaultBitladyBase = $reflection->getStaticPropertyValue('defaultBitladyBase');
        $refBase = $reflection->getProperty('bitladyBase'); //null value expected
        $refBasevalue = $refBase->getValue(); //null value expected
        $nval = $refDefaultBitladyBase*2;
        $refBase->setValue($nval);

        $class1 = new $this->classUseNullProps;
        $newBase = $class1::getBitladyBase();

        $this->assertSame($nval,$newBase,"When parent::bitladyBase is updated, BitladyTrait::defaultBitladyBase must be overriden");
    }

    /**
     * @desc: confirm that $this->getBitladyBase() would yield an Exception given that the
     * value of $this->bitladyBase is not valid 
     */
    public function testInvalidBitBaseSetAsOverride()
    {
        $reflection = new \ReflectionClass(get_class($this->classUseNullProps));
        $refBase = $reflection->getProperty('bitladyBase'); //null value expected
        $refBase->setValue(0);
        
        $class1 = new $this->classUseNullProps;

        $this->expectException('Exception');
        $this->expectExceptionMessageMatches("/bitladyBase must be between 2 to 64, and also be a product of the power of 2\$/");
        $class1::getBitladyBase();
    }
}