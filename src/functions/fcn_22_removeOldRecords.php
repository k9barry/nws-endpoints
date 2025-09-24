<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_22_removeOldRecords
 * 
 * Maintains database size by keeping only the most recent 999 incident records.
 * Deletes older incident records to prevent the database from growing too large
 * while preserving recent incident history for notifications and reference.
 *
 * @param PDO $db_conn Database connection (PDO instance)
 * @param string $db_incident Database table name containing incident records
 * @param int|string $CallId Current Call ID - records with IDs less than (CallId - 999) will be deleted
 * @param LoggerInterface $logger Logger instance for database cleanup operations
 * @return int Number of records deleted
 * @throws PDOException When database deletion fails
 */
function fcn_22_removeOldRecords(PDO $db_conn, string $db_incident, int|string $CallId, LoggerInterface $logger): int
{
    try {
        $threshold = (int) $CallId - 999; // Keep last 999 records
        
        if ($threshold <= 0) {
            $logger->info("No old records to delete (threshold: {$threshold})");
            return 0;
        }
        
        $sql = "DELETE FROM {$db_incident} WHERE db_CallId < ?";
        $stmt = $db_conn->prepare($sql);
        $result = $stmt->execute([$threshold]);
        
        if ($result) {
            $deletedCount = $stmt->rowCount();
            $logger->info("Deleted {$deletedCount} old incidents from table {$db_incident} where Call ID < {$threshold}");
            return $deletedCount;
        } else {
            throw new PDOException("Failed to delete old records from {$db_incident}");
        }
        
    } catch (PDOException $e) {
        $logger->error("Database error in fcn_22_removeOldRecords: " . $e->getMessage());
        throw $e;
    }
}
