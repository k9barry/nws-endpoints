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
function fcn_5_runExternal(
    string $strInFile, 
    string $strInRootFolder, 
    string $strOutFolder, 
    string $strBackupFolder, 
    mixed $logger, 
    string $db, 
    string $db_table
): void
{
    try {
        $logger->info("$strInFile => $strOutFolder");

        // Use DIRECTORY_SEPARATOR for compatibility
        $strRelativeFileName = ltrim(str_replace($strInRootFolder, '', $strInFile), DIRECTORY_SEPARATOR);
        $logger->info("RelativeFileName=$strRelativeFileName");

        // Prepare output file path
        $strOutFile = rtrim($strOutFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $strRelativeFileName;
        $strOutFile = preg_replace('#/+#', '/', $strOutFile);
        fcn_6_recursiveMkdir(dirname($strOutFile), $logger);
        $strOutFile = fcn_7_renameIfExists($strOutFile);

        // Database operations
        $db_conn = fcn_10_openConnection($db, $logger);
        if (!fcn_11_tableExists($db_conn, $db_table, $logger)) {
            fcn_12_createIncidentsTable($db_conn, $db_table, $logger);
        }
        fcn_13_recordReceived($db_conn, $db_table, $strInFile, $logger);

        // Explicitly close DB connection
        $db_conn = null;
        $logger->info("Connection to database closed");

        // Clean up old archives
        fcn_18_unlinkArchiveOld($strBackupFolder, $logger);

        // Prepare and move to backup/Archive folder
        $strBackupFile = rtrim($strBackupFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $strRelativeFileName;
        $strBackupFile = preg_replace('#/+#', '/', $strBackupFile);
        fcn_6_recursiveMkdir(dirname($strBackupFile), $logger);
        $strBackupFile = fcn_7_renameIfExists($strBackupFile);

        if (!@rename($strInFile, $strBackupFile)) {
            $logger->error("Failed to move file: $strInFile => $strBackupFile");
            throw new \RuntimeException("Unable to move file to archive folder");
        }
        $logger->info("MoveFile: $strInFile => $strBackupFile");

    } catch (\Throwable $e) {
        $logger->error("Exception in fcn_5_runExternal: " . $e->getMessage());
        // Optionally rethrow or handle differently
    }
}
