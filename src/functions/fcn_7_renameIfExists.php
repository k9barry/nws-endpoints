<?php

/**
 * fcn_7_renameIfExists
 *
 * @param  mixed $filename
 * @return $strNewFileName
 */
function fcn_7_renameIfExists($filename)
{
    if (!file_exists($filename)) {
        return $filename;
    }
    $arrayParts = pathinfo($filename);
    $strFolder = fcn_8_getValue($arrayParts, 'dirname');
    $strNewFileName = $strFolder . '//' . fcn_9_fileNewname($strFolder, fcn_8_getValue($arrayParts, 'basename'));
    $strNewFileName = str_replace('//', '/', $strNewFileName);
    return $strNewFileName;
}
