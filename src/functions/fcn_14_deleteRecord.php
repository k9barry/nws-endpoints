<?php

/**
 * fcn_14_deleteRecord
 *
 * @param  mixed $db_conn
 * @param string $db_incident
 * @param int $CallId
 * @param  mixed $logger
 * @return void
 */
function fcn_14_deleteRecord(mixed $db_conn, string $db_incident, int $CallId, mixed $logger): void
{
    $sql = "DELETE FROM $db_incident WHERE db_CallId = $CallId";
    $db_conn->exec($sql);
    $logger->info("Delete record " . $CallId . " from table " . $db_incident);
}
