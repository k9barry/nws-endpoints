<?php

/**
 * monitorFolder
 *
 * @param  mixed $strInFolder
 * @param  mixed $extensions
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @return void
 */
function monitorFolder($strInFolder, $extensions, $strOutFolder, $strBackupFolder)
{
    $strFilterFormat = globCaseInsensitivePattern($extensions);
    recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder);
}
