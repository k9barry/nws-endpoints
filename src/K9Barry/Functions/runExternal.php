<?php

/**
 * runExternal
 *
 * @param  mixed $strInFile
 * @param  mixed $strInRootFolder
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @return void
 */
function runExternal($strInFile, $strInRootFolder, $strOutFolder, $strBackupFolder)
{
    global $logger, $db, $db_table, $CfsTableName, $CfsCsvFilePath, $strLogFolder;
    $logger->info("$strInFile => $strOutFolder");
    $arrayParts = pathinfo($strInFile);
    $strRelativeFileName = str_replace($strInRootFolder, '', $strInFile);
    $logger->info("RelativeFileName=$strRelativeFileName");
    $strOutFile = $strOutFolder . '/' . $strRelativeFileName;
    $strOutFile = str_replace('//', '/', $strOutFile);
    recursive_mkdir(dirname($strOutFile));
    $strOutFile = renameIfExists($strOutFile);

    /*************************************************************************************************************************************
     * Add my custom functions to be preformed when new file is added to monitor folder
    /*************************************************************************************************************************************/
    $db_conn = new PDO("sqlite:$db");
    $logger->info("Connection opened to database $db");
    if (!tableExists($db_conn, $db_table)) {
        createIncidentsTable($db_conn, $db_table); // Create incidents table in DB if it does not exist
    }
    if (!tableExists($db_conn, $CfsTableName)) {
        csvToSqlite($db_conn, $CfsCsvFilePath, $options = array()); // Create CFS table in DB if it does not exist
    }
    recordReceived($db_conn, $db_table, $strInFile);
    closeConnection($db_conn);
    unlinkArchiveOld($strBackupFolder);
    unlinkLogFiles($strLogFolder);
    /*************************************************************************************************************************************/
    /*************************************************************************************************************************************/

    //Move original file to Archive folder
    $strBackupFile = $strBackupFolder . '/' . $strRelativeFileName;
    $strBackupFile = str_replace('//', '/', $strBackupFile);
    recursive_mkdir(dirname($strBackupFile));
    $strBackupFile = renameIfExists($strBackupFile);
    rename($strInFile, $strBackupFile);
    $logger->info("MoveFile: $strInFile => $strBackupFile");
}
