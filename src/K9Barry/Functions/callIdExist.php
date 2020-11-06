<?php

/**
 * callIdExist
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $CallId
 * @return $RowExists (true) if row exists
 */
function callIdExist($db_conn, $db_incident, $CallId)
{
    global $logger;
    $sql = "SELECT count(1) FROM $db_incident WHERE db_CallId = $CallId LIMIT 1";
    $result = $db_conn->query($sql);
    foreach ($result as $result) {
        $RowExists = $result[0];
    }
    if ($RowExists) {
        $logger->info("Call ID exists in database");
    } else {
        $logger->info("Call ID does not exist in database");
    }
    return $RowExists;
}
