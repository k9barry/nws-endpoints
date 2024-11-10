<?php

/**
 * fcn_2_monitorFolder
 *
 * @param  string $strInFolder
 * @param  array $extensions
 * @param  string $strOutFolder
 * @param  string $strBackupFolder
 * @param  mixed $logger
 * @return void
 */
function fcn_2_monitorFolder($strInFolder, $extensions, $strOutFolder, $strBackupFolder, $logger)
{
    $strFilterFormat = fcn_3_globCaseInsensitivePattern($extensions);

    fcn_4_recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder, $logger);
}