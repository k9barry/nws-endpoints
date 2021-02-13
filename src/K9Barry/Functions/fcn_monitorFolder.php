<?php

/**
 * fcn_monitorFolder
 *
 * @param  mixed $strInFolder
 * @param  mixed $extensions
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @param  mixed $logger
 * @return void
 */
function fcn_monitorFolder($strInFolder, $extensions, $strOutFolder, $strBackupFolder, $logger)
{
    $strFilterFormat = fcn_globCaseInsensitivePattern($extensions);
    fcn_recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder, $logger);
}
