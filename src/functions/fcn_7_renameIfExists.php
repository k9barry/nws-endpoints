<?php

/**
 * fcn_7_renameIfExists
 * 
 * Generates a unique filename if the specified file already exists.
 * Prevents file overwrites by creating a new filename with a counter suffix
 * when processing files that might have duplicate names.
 *
 * @param string $filename Full path to the file to check and potentially rename
 * @return string The original filename if it doesn't exist, or a unique filename
 */
function fcn_7_renameIfExists(string $filename): string
{
    if (!file_exists($filename)) {
        return $filename;
    }
    
    $arrayParts = pathinfo($filename);
    $strFolder = fcn_8_getValue($arrayParts, 'dirname', '.');
    $basename = fcn_8_getValue($arrayParts, 'basename', 'file');
    
    $strNewFileName = rtrim($strFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . fcn_9_fileNewname($strFolder, $basename);
    
    return $strNewFileName;
}
