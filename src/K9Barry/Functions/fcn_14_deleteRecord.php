<?php

/**
 * fcn_14_deleteRecord
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $CallId
 * @param  mixed $logger
 * @return void
 */
function fcn_14_deleteRecord($db_conn, $db_incident, $CallId, $logger)
{
    $sql = "DELETE FROM $db_incident WHERE db_CallId = $CallId";
    $db_conn->exec($sql);
    $logger->info("Delete record " . $CallId . " from table " . $db_incident . "");
}
