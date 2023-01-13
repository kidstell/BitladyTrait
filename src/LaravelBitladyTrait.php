<?php
declare(strict_types=1);

namespace Kidstell\Bitlady;

use Kidstell\Bitlady\BitladyTrait;

/**
 * Trait BitladyTrait
 * @package Kidstell\Bitlady
 */
trait LaravelBitladyTrait{

    use BitladyTrait;

    public function ScopeWhereBitSet($query, $columnName,$propertyId,$bitValue=true)
    {
        if(!$this->bitladyUseMultipleBlocks()){
            $bit = 1 << $propertyId;
            if($bitValue) return $query->where($columnName, '&', $bit);
            return $query->whereRaw("($columnName & $bit) != $bit");
        }
        $base = self::getBitladyBase();
        $width = BitladyCore::_blGetBitBaseWidth($base);
        list($level, $pos, $bit)=BitladyCore::_blGetPropertyBitInfo($propertyId,$base);
        $start = ($width * $level)+$level+1;
        $q='SUBSTR('.$columnName.', '.$start.', '.$width.') & '.$bit;
        $q=($bitValue)?$q:'('.$q.') != '.$bit;
        return $query->whereRaw($q);
    }

    public function ScopeWhereBitNotSet($query, $columnName,$propertyId,$bitValue=false)
    {
        return $this->ScopeWhereBitSet($query,$columnName,$propertyId,$bitValue);
    }
}