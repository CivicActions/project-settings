<?php

namespace CivicActions\ProjectSettings\Utility;

use ReflectionClass;

/**
 * Class BasicEnumTrait
 *
 * Provides constants validation for name/values.
 */
trait BasicEnumTrait
{
    private static $constCacheArray = null;

    /**
     * Check if name is valid.
     *
     * @param string $name
     *   Constant name
     * @param bool $strict
     *   If false, ignores case otherwise case sensitive.
     *
     * @return bool
     *   true if valid, false otherwise
     * @throws \ReflectionException
     */
    public static function isValidName($name, $strict = false)
    {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    /**
     * Get Class Constants.
     * @return array
     *   Array of constants
     * @throws \ReflectionException
     */
    public static function getConstants()
    {
        if (self::$constCacheArray == null) {
            self::$constCacheArray = [];
        }
        $called_class = get_called_class();
        if (!array_key_exists($called_class, self::$constCacheArray)) {
            $reflect = new ReflectionClass($called_class);
            self::$constCacheArray[$called_class] = $reflect->getConstants();
        }
        return self::$constCacheArray[$called_class];
    }

    /**
     * Check if value is valid.
     *
     * @param string $value
     *   Constant value
     * @param bool $strict
     *   If false, ignores case otherwise case sensitive.
     *
     * @return bool
     *   true if valid, false otherwise
     */
    public static function isValidValue($value, $strict = true)
    {
        try {
            $values = array_values(self::getConstants());
        } catch (\ReflectionException $e) {
            print "BasicEnumTrait::isValidValue() error: " . $e->getMessage();
        }
        return in_array($value, $values, $strict);
    }
}
