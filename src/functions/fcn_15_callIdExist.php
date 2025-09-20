<?php

/**
 * fcn_15_callIdExist
 * 
 * Checks if a specific Call ID already exists in the incident database.
 * Used to determine whether an incident is new (requiring notification) or 
 * an update to an existing incident (requiring change detection).
 *
 * @param mixed $db_conn Database connection (PDO instance)
 * @param string $db_incident Database table name for incident records
 * @param int $CallId New World CAD Call ID to check for existence
 * @param mixed $logger Logger instance for database query operations
 * @return mixed Returns 1 if Call ID exists, 0 if it doesn't exist
 */
function fcn_15_callIdExist(mixed $db_conn, string $db_incident, $CallId, mixed $logger): mixed
{
    $sql = "SELECT count(1) FROM $db_incident WHERE db_CallId = $CallId LIMIT 1";
    $result = $db_conn->query($sql);
    foreach ($result as $result) {
    }
    if ($result[0]) {
        $logger->info("Call ID exists in database");
    } else {
        $logger->info("Call ID does not exist in database");
    }
    return $result[0];
}
