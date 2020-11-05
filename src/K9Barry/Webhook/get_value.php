<?php

namespace K9Barry\Webhook;

class get_value
{
    /**
     * get_value
     *
     * @param  mixed $array
     * @param  mixed $index
     * @param  mixed $default
     * @return void
     */
    public function get_value($array, $index, $default = '')
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
}
