<?php

/**
 * fcn_22_removeOldRecords
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $CallId
 * @param  mixed $logger
 * @return void
 */
function fcn__removeOldRecords($db_conn, $db_incident, $CallId, $logger)
{
    $CallId = ($CallId - 999); //$CallId minus 999
    $sql = "DELETE FROM $db_incident WHERE db_CallId < $CallId";
    $db_conn->exec($sql);
    $logger->info("Delete all incidents from table " . $db_incident . " where Call ID is <= " . $CallId . "");
}