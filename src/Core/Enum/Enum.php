<?php
/**
 * Created by PhpStorm.
 * User: Xueron
 * Date: 2015/7/30
 * Time: 14:30
 */

namespace Dowedo\Core\Enum;


/**
 * Class Enum
 * @package Dowedo\Core\Enum
 */
abstract class Enum implements \JsonSerializable
{
    /**
     * @var
     */
    private $val;

    protected $constNameMap = [];

    /**
     * @param string $val
     */
    public function __construct($val = '__Dowedo_ENUM_DEFAULT_VALUE__')
    {
        $reflection = new \ReflectionClass(get_called_class());
        if ($reflection->hasConstant('__default')) {
            $defaultVal = $reflection->getConstant('__default');
            if (!static::isValid($defaultVal)) {
                throw new \OutOfRangeException(sprintf("Invalid __default enumeration %s for Enum %s", $defaultVal, get_class($this)));
            }
        }

        if ($val == '__Dowedo_ENUM_DEFAULT_VALUE__') {
            $this->setValue($defaultVal);
        } else {
            $this->setValue($val);
        }
    }

    /**
     * @param $val
     */
    public function setValue($val)
    {
        if (!static::isValid($val)) {
            throw new \InvalidArgumentException(sprintf("Invalid enumeration %s for Enum %s", $val, get_class($this)));
        }
        $this->val = $val;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->val;
    }

    public function getNameMap()
    {
        return $this->constNameMap;
    }

    public function getNames()
    {
        return array_flip($this->constNameMap);
    }

    /**
     * @param $val
     * @return bool
     */
    public static function isValid($val)
    {
        if (!in_array($val, static::validValues(), true)) {
            return false;
        }
        return true;
    }

    /**
     * @param bool|false $returnAssoc
     * @return array
     */
    public static function validValues($returnAssoc = false)
    {
        $reflection = new \ReflectionClass(get_called_class());
        $constants  = $reflection->getConstants();
        unset($constants['__default']);
        if ($returnAssoc) {
            return $constants;
        } else {
            return array_values($constants);
        }
    }

    /**
     * @inheritDoc
     */
    function jsonSerialize()
    {
        return $this->getValue();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (is_array($this->constNameMap) && array_key_exists($this->getValue(), $this->constNameMap)) {
            return (string) $this->constNameMap[$this->getValue()];
        }
        return (string) $this->getValue();
    }

    /**
     * @param $val
     * @return static
     */
    public static function init($val)
    {
        return new static($val);
    }

    /**
     * @param $val
     * @return static
     */
    public static function get($val)
    {
        return new static($val);
    }
}
