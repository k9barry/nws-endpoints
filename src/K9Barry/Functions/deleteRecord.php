<?php

/**
 * deleteRecord
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $CallId
 * @return void
 */
function deleteRecord($db_conn, $db_incident, $CallId)
{
    global $logger;
    $sql = "DELETE FROM $db_incident WHERE db_CallId = $CallId";
    $db_conn->exec($sql);
    $logger->info("Delete record " . $CallId . " from table " . $db_incident . "");
}