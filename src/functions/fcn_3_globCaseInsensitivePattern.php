<?php

/**
 * fcn_3_globCaseInsensitivePattern
 *  create case-insensitive patterns for glob or similar functions
 * ['jpg','gif'] as input
 * converted to: *.{[Jj][Pp][Gg],[Gg][Ii][Ff]}
 *
 * @param array $arr_extensions
 * @return string $outbound
 */
function fcn_3_globCaseInsensitivePattern(array $arr_extensions): string
{
    $outbound = '';
    $comma = '';
    foreach ($arr_extensions as $ext) {
        $outbound .= $comma;
        $comma = ',';
        foreach (str_split($ext) as $letter) {
            $outbound .= '[' . strtoupper($letter) . strtolower($letter) . ']';
        }
    }
    if (count($arr_extensions) == 1 && strlen($outbound) > 0) {
        return '*.' . $outbound;
    }
    if ($outbound) {
        return '*.{' . $outbound . '}';
    }
    // if no pattern given show all
    return '*';
}
