<?php

/**
 * fcn_2_monitorFolder
 *
 * @param  mixed $strInFolder
 * @param  mixed $extensions
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @param  mixed $logger
 * @return void
 */
function fcn_2_monitorFolder($strInFolder, $extensions, $strOutFolder, $strBackupFolder, $logger)
{
    $strFilterFormat = fcn_3_globCaseInsensitivePattern($extensions);
    fcn_4_recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder, $logger);
}
