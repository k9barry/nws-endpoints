<?php

/**
 * fcn_11_tableExists
 *
 * Check if a table exists in the current database.
 * @param mixed $db_conn PDO instance connected to a database.
 * @param string $db_incident table to search for.
 * @param mixed $logger
 * @return bool TRUE if table exists, FALSE if no table found.
 */
function fcn_11_tableExists(mixed $db_conn, string $db_incident, mixed $logger): bool
{
    // Try a select statement against the table
    // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
    try {
        $result = $db_conn->query("SELECT 1 FROM '$db_incident' LIMIT 1");
    } catch (Exception $e) {
        // We got an exception == table not found
        $logger->info("[fcn_11_tableExists] Table " . $db_incident . " not found with error " . $e->getMessage());
        return false;
    }
    // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
    return $result !== false;
}
