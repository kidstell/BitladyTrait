<?php
declare(strict_types=1);

namespace Kidstell\Bitlady;

use Kidstell\Bitlady\BitladyCore;
use Exception;

/**
 * Trait BitladyTrait
 * @package Kidstell\Bitlady
 */
trait BitladyTrait{

    protected static $defaultBitladyBase = 32;
    protected static $defaultBitladyUseSeparator = true;
    protected static $defaultBitladyUseMultipleBlocks = false;

    private $_magicFunctionCache = [];

    public function getPropertyState(string $stateVar, int $propertyId): bool
    {
        return BitladyCore::_blFilterBits($this->$stateVar,$propertyId,$this::getBitladyBase());
    }

    public function setPropertyState(string $stateVar, int $propertyId, bool $state): bool
    {
        list($allStates, $lastState) = BitladyCore::_blUpdateBit($this->$stateVar,$propertyId,$this::getBitladyBase(), $this::getBitladyUseSeparator(), $state,$this::bitladyUseMultipleBlocks());
        $this->$stateVar = $allStates;
        return $lastState;
    }

    public function togglePropertyState(string $stateVar, int $propertyId): bool
    {
        list($allStates, $lastState) = BitladyCore::_blToggleBit($this->$stateVar,$propertyId,$this::getBitladyBase(), $this::getBitladyUseSeparator(),$this::bitladyUseMultipleBlocks());
        $this->$stateVar = $allStates;
        return $lastState;
    }

    public static function getBitladyBase():int
    {
        $base = isset(self::$bitladyBase)?self::$bitladyBase:self::$defaultBitladyBase;
        if (in_array($base,[2,4,8,16,32,64])) return $base;
        throw new \Exception("bitladyBase must be between 2 to 64, and also be a product of the power of 2", 400);
    }

    public static function bitladyUseMultipleBlocks():bool
    {
        return isset(self::$bitladyUseMultipleBlocks)?self::$bitladyUseMultipleBlocks:self::$defaultBitladyUseMultipleBlocks;
    }

    public static function getBitladyUseSeparator():bool
    {
        return isset(self::$bitladyUseSeparator)?self::$bitladyUseSeparator:self::$defaultBitladyUseSeparator;
    }

    public function getBitLadyWatchers():array
    {
        return isset(self::$bitladyWatcher)?self::$bitladyWatcher:[];
    }

    private function _getVarForMagicFunctionCall($method):false|array
    {
        if (isset($this->_magicFunctionCache[$method])) {
            return $this->_magicFunctionCache[$method];
        }

        $stateVarsList = $this->getBitLadyWatchers();
        $snakedMethod = BitladyCore::camelToSnake($method);
        $methodParts = explode('_',$snakedMethod);
        $action = $methodParts[0];

        if (end($methodParts) !== 'bit' || !in_array($action, ['set','get','toggle','load','stringify'])) return false;

        $varName = BitladyCore::snakeToCamel(substr($method,strlen($methodParts[0]),-3));

        foreach ($stateVarsList as $k => $v) {
            if(is_int($k)) continue;
            if (BitladyCore::snakeToCamel($k) === $varName) {
                return $this->_magicFunctionCache[$method] = [$action,$v];
            }
        }

        $properties = get_object_vars($this);
        if(isset($properties['attributes']) && is_array($properties['attributes'])) $properties = array_merge($properties,$properties['attributes']); //an attempt to fish out laravel's object properties 
        foreach ($properties as $k => $v) {
            if (BitladyCore::snakeToCamel($k) === $varName) {
                return $this->_magicFunctionCache[$method] = [$action,$k];
            }
        }

        // the try block will address variable that can only be obtained from magic functions. however the variable name has to be camelCase
        try {
            $val = $this->$varName;
            return $this->_magicFunctionCache[$method] = [$action,$varName];
        } catch (\Throwable $th) {
            return false; //do nothing
        }
    }

    /**
     * To be called from the magic __call function. Checks if an undefined method has a name that follows Bitlady's convention.
     * 
     * @param  string  $method
     * @param  array  $parameters
     * @return bool
     */
    public function checkBitladyMagicWatch($method,$parameters): false|string
    {
        return (($result = $this->_getVarForMagicFunctionCall($method)) !== false)?$result[1]:false;
    }

    public function bitladyMagicAction(string $method,$parameters, bool $throwException=false)
    {
        list($action,$varName) = $ret = $this->_getVarForMagicFunctionCall($method);
        if ($ret === false) {
            // if(get_parent_class($this) !== false)
            //     return call_user_func(['parent',$method],...$parameters);
            if ($throwException) throw new Exception("BitladyTrait could not determine the variable name from the method called. Method: $method",404);
            return null;
        }

        $propertyId = isset($parameters[0])?$parameters[0]:null;
        if (is_null($propertyId)) {
            if ($throwException) throw new Exception("BitladyTrait: No property ID was defined in the method called. Method: $method",405);
            return -1;
        }

        $bitValue = isset($parameters[1])?$parameters[1]:null;
        return $this->bitladyOperator($action,$varName,$propertyId,$bitValue);
    }

    public function bitladyOperator(string $action, string $stateVar, int|array $propertyId, ?bool $bitValue)
    {
        switch ($action) {
            case 'set':
                return $this->setPropertyState($stateVar,$propertyId,$bitValue);
                break;
            case 'toggle':
                return $this->togglePropertyState($stateVar,$propertyId);
                break;
            case 'load':
                return $this->loadBitsToArray($stateVar,$propertyId,$bitValue??false);
                break;
            case 'stringify':
                return $this->stringifyBitsArray($stateVar,$propertyId);
                break;
            default:
                return $this->getPropertyState($stateVar,$propertyId);
                break;
        }
    }

    public function loadBitsToArray($stateVar,$propertyIds,$useKeysAsPropertyIds = false):array
    {   
        return BitladyCore::_blGetMultipleStates($this->$stateVar,$propertyIds,$this::getBitladyBase(),$useKeysAsPropertyIds);
    }


    public function stringifyBitsArray($stateVar,$propertyAndStates):string
    {   
        return $this->$stateVar = BitladyCore::_blUpdateMultipleStates($this->$stateVar, $propertyAndStates, $this::getBitladyBase(), $this::getBitladyUseSeparator());
    }

}