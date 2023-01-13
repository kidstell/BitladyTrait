<?php
declare(strict_types=1);

namespace Kidstell\Bitlady;

// use Exception;

/**
 * Class BitladyCore
 * @package Kidstell\Bitlady
 */
class BitladyCore{

    /**
     * Modifies one specific bit from a specific block of bits when given a long string composed of one or many blocks.
     * 
     * When a set of blocks or a single block called $allStates is provided, this function uses $propertyId to determine which bit on the block needs to be modified
     * 
     * @param string $... Optional. Description. Default.
     * @param string $allStates A string containing one or more blocks of bits.
     * @param int $propertyId This zero-based integer is iused to determine the specific bit and block that needs to be modified.
     * @param int $bitBase This indicates how many bits each block can contain, these bit bases are regular bit bases from system counting. Bases such as 2,4,8,16,32,64.
     * @param bool $useSeparator This indicates whether the blocks are separated by a pipe or otherwise.
     * @param bool $useMultiBlock This indicates if the states should contain multiple blocks or not.
     * @return array The already modified states and the boolean value stored in the modified bit.
     */
    public static function _blUpdateBit(string $allStates, int $propertyId, int $bitBase, bool $useSeparator, bool $state,$useMultiBlock): array
    {
        list($level, $pos, $bit)=self::_blGetPropertyBitInfo($propertyId,$bitBase);
        if ( !$useMultiBlock && $level > 0) throw new \Exception("The Property ID which you tried to modify is OutOfRange, You may enable 'bitladyUseMultipleBlocks', but this may break existing data already stored on DB", 1);

        $blocks = self::_blTransformStatesToLevels($allStates,$bitBase);
        $blocks[$level] = self::_blUpdateBitBlock(isset($blocks[$level])?$blocks[$level]:"0",$bit,$state);
        $allStates = self::_blMergeBlocks($blocks,$bitBase,$useSeparator);

        return [
            $allStates,
            (($blocks[$level] & $bit) === $bit)
        ];
    }

    /**
     * Sets or unsets a specific bit in a given block
     * 
     * When given a block, sets a specific bit to 1 or 0 depending on the value of $state  
     * 
     * @param string $block A numeric string representing a decimal number. This number, when converted to binary gives a bigger view of which bits are turned on or off.
     * @param int $bit This binary has both 1's and 0's, the 1's are the set-bits. The set-bits indicate what positions would be updated and updated in the $block.
     * @param int $state This indicates whether the targeted bits would be set to 1s or 0s.
     * @return int The already modified $block.
     */
    public static function _blUpdateBitBlock(string $block, int $bit, bool $state): int
    {
        if ($state) {
            $block |= $bit;
        } else {
            $block &= ~$bit;
        }

        return $block;
    }

    /**
     * Toggles one specific bit from a specific block of bits when given a long string composed of one or many blocks.
     * 
     * When a set of blocks or a single block called $allStates is provided, this function uses $propertyId to determine which bit on the block needs to be toggled.
     * 
     * @param string $... Optional. Description. Default.
     * @param string $allStates A string containing one or more blocks of bits.
     * @param int $propertyId This zero-based integer is iused to determine the specific bit and block that needs to be toggled.
     * @param int $bitBase This indicates how many bits each block can contain, these bit bases are regular bit bases from system counting. Bases such as 2,4,8,16,32,64.
     * @param bool $useSeparator This indicates whether the blocks are separated by a pipe or otherwise.
     * @param bool $useMultiBlock This indicates if the states should contain multiple blocks or not.
     */
    public static function _blToggleBit(string $allStates, int $propertyId, int $bitBase, bool $useSeparator,$useMultiBlock): array
    {
        list($level, $pos, $bit)=self::_blGetPropertyBitInfo($propertyId,$bitBase);
        if (!$useMultiBlock && $level > 0) throw new \Exception("The Property ID which you tried to modify is OutOfRange, You may enable 'bitladyUseMultipleBlocks', but this may break existing data already stored on DB", 1);

        $blocks = self::_blTransformStatesToLevels($allStates,$bitBase);
        $blocks[$level] = self::_blToggleBitBlock(isset($blocks[$level])?$blocks[$level]:'0',$bit);
        $allStates = self::_blMergeBlocks($blocks,$bitBase,$useSeparator);
        
        return [
            $allStates,
            (($blocks[$level] & $bit) === $bit)
        ];
    }

    /**
     * Toggles a bit in a Block and returns the modified block.
     * 
     * When given a block, toggles a specific $bit $state  
     * 
     * @param string $block A numeric string representing a decimal number. This number converted to binary gives a bigger view of which bits are turned on or off.
     * @param int $bit This binary has both 1's and 0's, the 1's are the set bits. the sets bits would be targeted in the block and updated as required.
     */
    public static function _blToggleBitBlock(string $block, int $bit): int
    {
        $block ^= $bit;
        return $block;
    }

    /**
     * Coverts an array of blocks to an accepted string
     * 
     * When given blocks, merge the array values into a string
     * 
     * @param array $blocks A numeric string representing a decimal number. This number converted to binary gives a bigger view of which bits are turned on or off.
     * @param int $bitBase This indicates how many bits each block can contain, these bit bases are regular bit bases from system counting. Bases such as 2,4,8,16,32,64.
     * @param bool $useSeparator This indicates whether the output string separate by a pipe or otherwise.
     */
    public static function _blMergeBlocks(array $blocks, int $bitBase, bool $useSeparator): string
    {
        $m = max(array_keys($blocks));
        $width = self::_blGetBitBaseWidth($bitBase);
        $ret = [];

        for ($i=0; $i <= $m; $i++) {
            $ret[$i] = isset($blocks[$i])?$blocks[$i]:0;
            $ret[$i] = str_pad(strval($ret[$i]),$width,'0',STR_PAD_LEFT);
        }
        return implode(($useSeparator)?'|':'',$ret);
    }

    public static function _blFilterBits(string $allStates, int $propertyId, int $bitBase):bool
    {
        list($level, , $bit) = self::_blGetPropertyBitInfo($propertyId,$bitBase);
        $bitBlock = self::_blGetBitBlock($allStates,$level,$bitBase);
        return ($bitBlock & $bit) === $bit;
    }

    public static function _blGetPropertyBitInfo(int $propertyId, int $bitBase):array
    {
        $level=(int)floor($propertyId/$bitBase);
        $pos = ($propertyId - ($level * $bitBase));
        $bit = 1 << $pos;

        return [$level, $pos, $bit];
    }

    public static function _blGetBitBlock(string $allStates, int $level, int $bitBase):string
    {
        $blocks = self::_blTransformStatesToLevels($allStates,$bitBase);
        return isset($blocks[$level])?$blocks[$level]:'0';
    }

    public static function _blGetBitBaseWidth(int $bitBase):int
    {
        $res = [2 => 1, 4 => 2, 8 => 3, 16 => 5, 32 => 10, 64 => 18];
        return ($res[$bitBase])?:throw new \Exception("The bit base ($bitBase) provided is invalid", 1);
    }
    /**
     * converts a longstring to array of blocks.
     * @param string $allStates 
     */
    public static function _blTransformStatesToLevels(string $allStates, int $bitBase):array
    {
        if (strpos($allStates,'|')) {
            return explode('|',$allStates);
        }
        $width = self::_blGetBitBaseWidth($bitBase);
        return str_split($allStates,$width);
    }

    public static function _blGetMultipleStates(string $allStates,array $propertyIds, int $bitBase, bool $useKeysAsPropertyIds):array
    {
        if($useKeysAsPropertyIds){$propertyIds = array_keys($propertyIds);}
        $resp = [];
        $blocks = self::_blTransformStatesToLevels($allStates,$bitBase);

        foreach ($propertyIds as $propertyId) {
            list($level, , $bit) = self::_blGetPropertyBitInfo($propertyId,$bitBase);
            $bitBlock = isset($blocks[$level])?$blocks[$level]:'0';
            $resp[$propertyId] = ($bitBlock & $bit) === $bit;
        }
        return $resp;
    }

    public static function _blUpdateMultipleStates(string $initialState, array $propertyStates, int $bitBase, bool $useSeparator):string
    {
        $blocks = self::_blTransformStatesToLevels($initialState,$bitBase);

        foreach ($propertyStates as $propertyId => $state) {
            list($level, , $bit) = self::_blGetPropertyBitInfo($propertyId,$bitBase);
            $blocks[$level] = isset($blocks[$level])?$blocks[$level]:'0';
            $blocks[$level] = self::_blUpdateBitBlock((string)$blocks[$level], $bit, $state);
        }

        return (string)self::_blMergeBlocks($blocks,$bitBase,$useSeparator);
    }

    public static function camelToSnake($input):string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    public static function snakeToCamel($input):string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }


}