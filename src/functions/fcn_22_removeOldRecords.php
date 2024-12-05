<?php

/**
 * fcn_22_removeOldRecords Only keep the last 999 Call ID's
 *
 * @param  mixed $db_conn
 * @param string $db_incident
 * @param int $CallId
 * @param  mixed $logger
 * @return void
 */
function fcn_22_removeOldRecords(mixed $db_conn, string $db_incident, $CallId, mixed $logger): void
{
    $CallId = ($CallId - 999); //$CallId minus 999
    $sql = "DELETE FROM $db_incident WHERE db_CallId < $CallId";
    $db_conn->exec($sql);
    $logger->info("Delete all incidents from table " . $db_incident . " where Call ID is <= " . $CallId);
}
