<?php

/**
 * fcn_renameIfExists
 *
 * @param  mixed $filename
 * @return void
 */
function fcn_renameIfExists($filename)
{
    if (!file_exists($filename)) {
        return $filename;
    }
    $arrayParts = pathinfo($filename);
    $strFolder = fcn_get_value($arrayParts, 'dirname');
    $strNewFileName = $strFolder . '//' . fcn_fileNewname($strFolder, fcn_get_value($arrayParts, 'basename'));
    $strNewFileName = str_replace('//', '/', $strNewFileName);
    return $strNewFileName;
}
