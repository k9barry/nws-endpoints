<?php

/**
 * fcn_22_removeOldRecords
 * 
 * Maintains database size by keeping only the most recent 999 incident records.
 * Deletes older incident records to prevent the database from growing too large
 * while preserving recent incident history for notifications and reference.
 *
 * @param mixed $db_conn Database connection (PDO instance)
 * @param string $db_incident Database table name containing incident records
 * @param int $CallId Current Call ID - records with IDs less than (CallId - 999) will be deleted
 * @param mixed $logger Logger instance for database cleanup operations
 * @return void
 */
function fcn_22_removeOldRecords(mixed $db_conn, string $db_incident, $CallId, mixed $logger): void
{
    $CallId = ($CallId - 999); //$CallId minus 999
    $sql = "DELETE FROM $db_incident WHERE db_CallId < $CallId";
    $db_conn->exec($sql);
    $logger->info("Delete all incidents from table " . $db_incident . " where Call ID is <= " . $CallId);
}
