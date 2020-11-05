<?php

namespace K9Barry\Webhook;

class globCaseInsensitivePattern
{
    /**
     * globCaseInsensitivePattern
     * * create case insensitive patterns for glob or simular functions
     * ['jpg','gif'] as input
     * converted to: *.{[Jj][Pp][Gg],[Gg][Ii][Ff]}
     *
     * @param  mixed $arr_extensions
     * @return void
     */
    public function globCaseInsensitivePattern($arr_extensions)
    {
        $opbouw = '';
        $comma = '';
        foreach ($arr_extensions as $ext) {
            $opbouw .= $comma;
            $comma = ',';
            foreach (str_split($ext) as $letter) {
                $opbouw .= '[' . strtoupper($letter) . strtolower($letter) . ']';
            }
        }
        if (count($arr_extensions) == 1 && strlen($opbouw) > 0) {
            return '*.' . $opbouw;
        }
        if ($opbouw) {
            return '*.{' . $opbouw . '}';
        }
        // if no pattern given show all
        return '*';
    }
}
