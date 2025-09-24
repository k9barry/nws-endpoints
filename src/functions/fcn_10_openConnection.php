<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_10_openConnection
 * 
 * Opens a connection to the SQLite database for incident storage.
 * Creates a PDO connection that will be used for storing and retrieving
 * New World CAD incident data throughout the processing workflow.
 *
 * @param string $db Database file path (SQLite database)
 * @param LoggerInterface $logger Logger instance for database connection operations
 * @return PDO Database connection object
 * @throws PDOException When database connection fails
 */
function fcn_10_openConnection(string $db, LoggerInterface $logger): PDO
{
    try {
        $db_conn = new PDO("sqlite:$db");
        $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $logger->info("Connection opened to database " . $db);
        return $db_conn;
    } catch (PDOException $e) {
        $logger->error("Failed to connect to database {$db}: " . $e->getMessage());
        throw $e;
    }
}
