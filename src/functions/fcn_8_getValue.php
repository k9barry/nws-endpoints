<?php

/**
 * fcn_8_getValue
 *
 * @param array $array
 * @param mixed $index
 * @param string|null $default
 * @return string|null $value
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
