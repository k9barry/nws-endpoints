<?php

/**
 * fcn_8_getValue
 * 
 * Safely retrieves a value from an array with default fallback and trimming.
 * Prevents errors when accessing array keys that might not exist and
 * handles empty string values by returning the default instead.
 *
 * @param array $array The array to retrieve the value from
 * @param mixed $index The array key/index to access
 * @param string|null $default Default value to return if key doesn't exist or value is empty
 * @return string|null The trimmed value from the array or the default value
 */
function fcn_8_getValue(array $array, mixed $index, null|string $default = ''): ?string
{
    if (!isset($array[$index])) {
        return $default;
    }
    $value = trim($array[$index]);
    if (strlen($value) <= 0) {
        return $default;
    }
    return $value;
}
