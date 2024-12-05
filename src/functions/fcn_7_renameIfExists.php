<?php

/**
 * fcn_7_renameIfExists
 *
 * @param mixed $filename
 * @return array|mixed|string|string[] $strNewFileName
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
