<?php

/**
 * fcn_10_openConnection
 * 
 * Opens a connection to the SQLite database for incident storage.
 * Creates a PDO connection that will be used for storing and retrieving
 * New World CAD incident data throughout the processing workflow.
 *
 * @param string $db Database file path (SQLite database)
 * @param mixed $logger Logger instance for database connection operations
 * @return PDO Database connection object
 */
function fcn_10_openConnection(string $db, mixed $logger): PDO
{
    $db_conn = new PDO("sqlite:$db");
    $logger->info("Connection opened to database " . $db);
    return $db_conn;
}
