<?php

/**
 * fcn_5_runExternal
 * 
 * Processes a single New World CAD file through the complete workflow.
 * Handles file movement, database operations, and triggers notification sending.
 * This is the main processing function that coordinates all incident handling operations.
 *
 * @param string $strInFile Full path to the input file to process
 * @param string $strInRootFolder Root input folder for relative path calculations
 * @param string $strOutFolder Output folder for temporary file processing
 * @param string $strBackupFolder Archive folder for storing processed files
 * @param mixed $logger Logger instance for processing operations
 * @param string $db Database file path for incident storage
 * @param string $db_table Database table name for incident records
 * @return void
 */
function fcn_5_runExternal(string $strInFile, string $strInRootFolder, string $strOutFolder, string $strBackupFolder, mixed $logger, string $db, string $db_table): void
{
    $logger->info("$strInFile => $strOutFolder");
    $strRelativeFileName = str_replace($strInRootFolder, '', $strInFile);
    $logger->info("RelativeFileName=$strRelativeFileName");
    $strOutFile = $strOutFolder . '/' . $strRelativeFileName;
    $strOutFile = str_replace('//', '/', $strOutFile);
    fcn_6_recursiveMkdir(dirname($strOutFile), $logger);
    fcn_7_renameIfExists($strOutFile);

    /*************************************************************************************************************************************
     * Add my custom functions to be preformed when new file is added to monitor folder
    /*************************************************************************************************************************************/
    $db_conn = fcn_10_openConnection($db, $logger);
    if (!fcn_11_tableExists($db_conn, $db_table, $logger)) {
        fcn_12_createIncidentsTable($db_conn, $db_table, $logger); // Create incidents table in DB if it does not exist
    }
    fcn_13_recordReceived($db_conn, $db_table, $strInFile, $logger);
    $db_conn = null;
    $logger->info("Connection to database closed");
    fcn_18_unlinkArchiveOld($strBackupFolder, $logger);

    //Move original file to Archive folder
    $strBackupFile = $strBackupFolder . '/' . $strRelativeFileName;
    $strBackupFile = str_replace('//', '/', $strBackupFile);
    fcn_6_recursiveMkdir(dirname($strBackupFile), $logger);
    $strBackupFile = fcn_7_renameIfExists($strBackupFile);
    rename($strInFile, $strBackupFile);
    $logger->info("MoveFile: $strInFile => $strBackupFile");
}
