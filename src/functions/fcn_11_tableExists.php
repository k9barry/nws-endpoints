<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_11_tableExists
 *
 * Check if a table exists in the current database.
 * Uses proper SQLite system table query instead of trying to select from potentially non-existent table.
 * 
 * @param PDO $db_conn PDO instance connected to a database
 * @param string $db_incident Table name to search for
 * @param LoggerInterface $logger Logger instance for database operations
 * @return bool TRUE if table exists, FALSE if no table found
 */
function fcn_11_tableExists(PDO $db_conn, string $db_incident, LoggerInterface $logger): bool
{
    try {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
        $stmt = $db_conn->prepare($sql);
        $stmt->execute([$db_incident]);
        $result = $stmt->fetch();
        
        if ($result) {
            $logger->info("[fcn_11_tableExists] Table {$db_incident} exists");
            return true;
        } else {
            $logger->info("[fcn_11_tableExists] Table {$db_incident} not found");
            return false;
        }
    } catch (PDOException $e) {
        $logger->error("[fcn_11_tableExists] Error checking table existence for {$db_incident}: " . $e->getMessage());
        return false;
    }
}
