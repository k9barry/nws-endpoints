<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_15_callIdExist
 * 
 * Checks if a specific Call ID already exists in the incident database.
 * Used to determine whether an incident is new (requiring notification) or 
 * an update to an existing incident (requiring change detection).
 *
 * @param PDO $db_conn Database connection (PDO instance)
 * @param string $db_incident Database table name for incident records
 * @param int|string $CallId New World CAD Call ID to check for existence
 * @param LoggerInterface $logger Logger instance for database query operations
 * @return bool Returns true if Call ID exists, false if it doesn't exist
 * @throws PDOException When database query fails
 */
function fcn_15_callIdExist(PDO $db_conn, string $db_incident, int|string $CallId, LoggerInterface $logger): bool
{
    try {
        $sql = "SELECT COUNT(1) FROM {$db_incident} WHERE db_CallId = ? LIMIT 1";
        $stmt = $db_conn->prepare($sql);
        $stmt->execute([$CallId]);
        $result = $stmt->fetchColumn();
        
        $exists = (bool) $result;
        if ($exists) {
            $logger->info("Call ID {$CallId} exists in database");
        } else {
            $logger->info("Call ID {$CallId} does not exist in database");
        }
        
        return $exists;
    } catch (PDOException $e) {
        $logger->error("Failed to check if Call ID {$CallId} exists in table {$db_incident}: " . $e->getMessage());
        throw $e;
    }
}
