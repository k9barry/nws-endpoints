<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_14_deleteRecord
 * 
 * Removes a specific incident record from the database when it's marked as closed.
 * Called when New World CAD sends an incident with ClosedFlag = true, indicating
 * the incident is resolved and should be removed from active monitoring.
 *
 * @param PDO $db_conn Database connection (PDO instance)
 * @param string $db_incident Database table name for incident records
 * @param int|string $CallId New World CAD Call ID to delete from the database
 * @param LoggerInterface $logger Logger instance for record deletion operations
 * @return bool Returns true if record was deleted, false otherwise
 * @throws PDOException When database deletion fails
 */
function fcn_14_deleteRecord(PDO $db_conn, string $db_incident, int|string $CallId, LoggerInterface $logger): bool
{
    try {
        $sql = "DELETE FROM {$db_incident} WHERE db_CallId = ?";
        $stmt = $db_conn->prepare($sql);
        $result = $stmt->execute([$CallId]);
        
        if ($result && $stmt->rowCount() > 0) {
            $logger->info("Deleted record {$CallId} from table {$db_incident}");
            return true;
        } else {
            $logger->warning("No record found to delete for CallId {$CallId} in table {$db_incident}");
            return false;
        }
    } catch (PDOException $e) {
        $logger->error("Failed to delete record {$CallId} from table {$db_incident}: " . $e->getMessage());
        throw $e;
    }
}
