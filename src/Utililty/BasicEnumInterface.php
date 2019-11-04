<?php

namespace CivicActions\ProjectSettings\Utility;

/**
 * Class BasicEnumTraitInterface
 *
 * Provides constants validation for name/values.
 */
interface BasicEnumInterface
{
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
    public static function isValidName($name, $strict = false);

    /**
     * Get Class Constants.
     * @return array
     *   Array of constants
     * @throws \ReflectionException
     */
    public static function getConstants();

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
    public static function isValidValue($value, $strict = true);
}
