<?php

/**
 * fcn_2_monitorFolder
 *
 * @param string $strInFolder
 * @param array $extensions
 * @param string $strOutFolder
 * @param string $strBackupFolder
 * @param mixed $logger
 * @param string $db
 * @param string $db_table
 * @return void
 */
function fcn_2_monitorFolder(string $strInFolder, array $extensions, string $strOutFolder, string $strBackupFolder, mixed $logger, string $db, string $db_table): void
{
    $strFilterFormat = fcn_3_globCaseInsensitivePattern($extensions);

    fcn_4_recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder, $logger, $db, $db_table);
}
