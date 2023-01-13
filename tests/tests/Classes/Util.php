<?php
namespace Kidstell\Bitlady\Tests\Classes;

use Kidstell\Bitlady\BitladyCore;

class Util{

    /* 
    Desc: generaate a set of random binary integers (1 or 0) to the specified length
    */
    public static function randomBits($len)
    {
        $bits = '';
        for ($i=0; $i < $len; $i++) {
            list($usec, $sec) = explode(' ', microtime());
            mt_srand($sec + $usec * 1000000);
            $bits.=(mt_rand() & 1);
        }
        return $bits;
    }

    /* 
    Desc: genrate a random bit for a set of specified positions on the result
    $arr: (array) the positions where we desire to set a random bit
    $bitBase: (int) number of binary digits allowed before a new block is created
    $useSeparator: (bool) whether to separate each block or simply concatenate the blocks
    $useKeys: (bool) whether the property Ids are stored as keys or not in the $arr  
    */
    public static function generateBitByKeys($arr,$bitBase,$useSeparator=true,$useKeys=true)
    {
        $result = '0';
        foreach ($arr as $key => $value) {
            $propId = ($useKeys)?$key:$value;
            $result = BitladyCore::_blUpdateBit((string)$result,$propId,$bitBase,$useSeparator,Util::randomBits(1),true)[0];
        }
        return $result;
    }
}
