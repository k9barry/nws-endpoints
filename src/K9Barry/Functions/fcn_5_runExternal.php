<?php

/**
 * fcn_5_runExternal
 *
 * @param  mixed $strInFile
 * @param  mixed $strInRootFolder
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @param  mixed $logger
 * @return void
 */
function fcn_5_runExternal($strInFile, $strInRootFolder, $strOutFolder, $strBackupFolder, $logger)
{
    global $db, $db_table, $CfsTableName, $CfsCsvFilePath, $strLogFolder;
    $logger->info("$strInFile => $strOutFolder");
    $arrayParts = pathinfo($strInFile);
    $strRelativeFileName = str_replace($strInRootFolder, '', $strInFile);
    $logger->info("RelativeFileName=$strRelativeFileName");
    $strOutFile = $strOutFolder . '/' . $strRelativeFileName;
    $strOutFile = str_replace('//', '/', $strOutFile);
    fcn_6_recursiveMkdir(dirname($strOutFile), 0755, true, $logger);
    $strOutFile = fcn_7_renameIfExists($strOutFile);

    /*************************************************************************************************************************************
     * Add my custom functions to be preformed when new file is added to monitor folder
    /*************************************************************************************************************************************/
    $db_conn = fcn_10_openConnection($db, $logger);
//   $db_conn = new PDO("sqlite:$db");
//   $logger->info("Connection opened to database $db");
    if (!fcn_11_tableExists($db_conn, $db_table, $logger)) {
        fcn_12_createIncidentsTable($db_conn, $db_table, $logger); // Create incidents table in DB if it does not exist
    }
    fcn_13_recordReceived($db_conn, $db_table, $strInFile, $logger);
    fcn_17_closeConnection($db_conn, $logger);
    fcn_18_unlinkArchiveOld($strBackupFolder);
    fcn_19_unlinkLogFiles($strLogFolder, $logger);
    /*************************************************************************************************************************************/
    /*************************************************************************************************************************************/

    //Move original file to Archive folder
    $strBackupFile = $strBackupFolder . '/' . $strRelativeFileName;
    $strBackupFile = str_replace('//', '/', $strBackupFile);
    fcn_6_recursiveMkdir(dirname($strBackupFile), 0755, true, $logger);
    $strBackupFile = fcn_7_renameIfExists($strBackupFile);
    rename($strInFile, $strBackupFile);
    $logger->info("MoveFile: $strInFile => $strBackupFile");
}
