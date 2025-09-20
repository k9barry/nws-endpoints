<?php

/**
 * fcn_3_globCaseInsensitivePattern
 * 
 * Creates case-insensitive file extension patterns for use with glob() function.
 * Converts file extensions into patterns that match regardless of case sensitivity.
 * Example: ['jpg','gif'] becomes '*.{[Jj][Pp][Gg],[Gg][Ii][Ff]}'
 * 
 * This ensures New World CAD files are found regardless of filename case.
 *
 * @param array $arr_extensions Array of file extensions to create patterns for
 * @return string Glob pattern string for case-insensitive file matching
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
