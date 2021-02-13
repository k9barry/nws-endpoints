<?php

/**
 * fcn_runExternal
 *
 * @param  mixed $strInFile
 * @param  mixed $strInRootFolder
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @param  mixed $logger
 * @return void
 */
function fcn_runExternal($strInFile, $strInRootFolder, $strOutFolder, $strBackupFolder, $logger)
{
    global $db, $db_table, $CfsTableName, $CfsCsvFilePath, $strLogFolder;
    $logger->info("$strInFile => $strOutFolder");
    $arrayParts = pathinfo($strInFile);
    $strRelativeFileName = str_replace($strInRootFolder, '', $strInFile);
    $logger->info("RelativeFileName=$strRelativeFileName");
    $strOutFile = $strOutFolder . '/' . $strRelativeFileName;
    $strOutFile = str_replace('//', '/', $strOutFile);
    fcn_recursiveMkdir(dirname($strOutFile), 0755, true, $logger);
    $strOutFile = fcn_renameIfExists($strOutFile);

    /*************************************************************************************************************************************
     * Add my custom functions to be preformed when new file is added to monitor folder
    /*************************************************************************************************************************************/
    $db_conn = fcn_openConnection($db, $logger);
//   $db_conn = new PDO("sqlite:$db");
 //   $logger->info("Connection opened to database $db");
    if (!fcn_tableExists($db_conn, $db_table, $logger)) {
        fcn_createIncidentsTable($db_conn, $db_table, $logger); // Create incidents table in DB if it does not exist
    }
    if (!fcn_tableExists($db_conn, $CfsTableName, $logger)) {
        fcn_csvToSqlite($db_conn, $CfsCsvFilePath, $options = array(), $logger); // Create CFS table in DB if it does not exist
    }
    fcn_recordReceived($db_conn, $db_table, $strInFile, $logger);
    fcn_closeConnection($db_conn, $logger);
    fcn_unlinkArchiveOld($strBackupFolder);
    fcn_unlinkLogFiles($strLogFolder, $logger);
    /*************************************************************************************************************************************/
    /*************************************************************************************************************************************/

    //Move original file to Archive folder
    $strBackupFile = $strBackupFolder . '/' . $strRelativeFileName;
    $strBackupFile = str_replace('//', '/', $strBackupFile);
    fcn_recursiveMkdir(dirname($strBackupFile), 0755, true, $logger);
    $strBackupFile = fcn_renameIfExists($strBackupFile);
    rename($strInFile, $strBackupFile);
    $logger->info("MoveFile: $strInFile => $strBackupFile");
}
