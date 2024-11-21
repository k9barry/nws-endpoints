<?php

/**
 * fcn_8_getValue
 *
 * @param  array $array
 * @param  mixed $index
 * @param  mixed $default
 * @return $value
 */
function fcn_8_getValue($array, $index, $default = '')
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
