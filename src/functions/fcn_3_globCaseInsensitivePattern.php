<?php
/**
 * fcn_3_globCaseInsensitivePattern
 *
 * Builds a case-insensitive glob pattern for matching file extensions.
 * Example: ['jpg', 'gif'] results in '*.{[Jj][Pp][Gg],[Gg][Ii][Ff]}'
 * Handles empty input and ignores empty/invalid extensions.
 *
 * @param array $arr_extensions Array of file extensions (without dot)
 * @return string Glob pattern for case-insensitive file matching
 */
function fcn_3_globCaseInsensitivePattern(array $arr_extensions): string
{
    if (empty($arr_extensions)) {
        return '*';
    }

    $patterns = [];

    foreach ($arr_extensions as $ext) {
        $ext = trim($ext, " .");
        if ($ext === '') {
            continue;
        }

        // Build the pattern for this extension, supporting multi-part extensions
        $pattern = '';
        foreach (explode('.', $ext) as $i => $part) {
            if ($i > 0) {
                $pattern .= '\\.'; // literal dot between parts
            }
            foreach (str_split($part) as $letter) {
                $pattern .= '[' . strtoupper($letter) . strtolower($letter) . ']';
            }
        }
        $patterns[] = $pattern;
    }

    if (empty($patterns)) {
        return '*';
    }

    // Single extension: no braces
    if (count($patterns) === 1) {
        return '*.' . $patterns[0];
    }

    // Multiple extensions: wrap in {}
    return '*.{'.implode(',', $patterns).'}';
}
