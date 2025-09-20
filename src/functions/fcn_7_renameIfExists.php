<?php

/**
 * fcn_7_renameIfExists
 * 
 * Generates a unique filename if the specified file already exists.
 * Prevents file overwrites by creating a new filename with a counter suffix
 * when processing files that might have duplicate names.
 *
 * @param mixed $filename Full path to the file to check and potentially rename
 * @return array|mixed|string|string[] The original filename if it doesn't exist, or a unique filename
 */
function fcn_7_renameIfExists(mixed $filename): mixed
{
    if (!file_exists($filename)) {
        return $filename;
    }
    $arrayParts = pathinfo($filename);
    $strFolder = fcn_8_getValue($arrayParts, 'dirname');
    $strNewFileName = $strFolder . '//' . fcn_9_fileNewname($strFolder, fcn_8_getValue($arrayParts, 'basename'));
    return str_replace('//', '/', $strNewFileName);
}
