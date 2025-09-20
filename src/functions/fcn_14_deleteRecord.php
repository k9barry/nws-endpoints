<?php

/**
 * fcn_14_deleteRecord
 * 
 * Removes a specific incident record from the database when it's marked as closed.
 * Called when New World CAD sends an incident with ClosedFlag = true, indicating
 * the incident is resolved and should be removed from active monitoring.
 *
 * @param mixed $db_conn Database connection (PDO instance)
 * @param string $db_incident Database table name for incident records
 * @param int $CallId New World CAD Call ID to delete from the database
 * @param mixed $logger Logger instance for record deletion operations
 * @return void
 */
function fcn_14_deleteRecord(mixed $db_conn, string $db_incident, $CallId, mixed $logger): void
{
    $sql = "DELETE FROM $db_incident WHERE db_CallId = $CallId";
    $db_conn->exec($sql);
    $logger->info("Delete record " . $CallId . " from table " . $db_incident);
}
