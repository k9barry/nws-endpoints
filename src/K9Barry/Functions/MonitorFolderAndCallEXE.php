<?php

/**
 * MonitorFolderAndCallEXE
 *
 * @param  mixed $strInFolder
 * @param  mixed $extensions
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @return void
 */
function MonitorFolderAndCallEXE($strInFolder, $extensions, $strOutFolder, $strBackupFolder)
{
    $strFilterFormat = globCaseInsensitivePattern($extensions);
    recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder);
}
