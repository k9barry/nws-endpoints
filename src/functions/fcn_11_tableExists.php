<?php

/**
 * fcn_11_tableExists
 *
 * Check if a table exists in the current database.
 * @param PDO $db_conn PDO instance connected to a database.
 * @param string $db_incident table to search for.
 * @param string $logger
 * @return bool TRUE if table exists, FALSE if no table found.
 */
function fcn_11_tableExists($db_conn, $db_incident, $logger)
{
    // Try a select statement against the table
    // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
    try {
        $result = $db_conn->query("SELECT 1 FROM '$db_incident' LIMIT 1");
    } catch (Exception $e) {
        // We got an exception == table not found
        $logger->info("[fcn_11_tableExists] Table " . $db_incident . " not found");
        return false;
    }
    // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
    return $result !== false;
}