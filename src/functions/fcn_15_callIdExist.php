<?php

/**
 * fcn_15_callIdExist
 *
 * @param  mixed $db_conn
 * @param  string $db_incident
 * @param  int $CallId
 * @param  mixed $logger
 * @return $RowExists
 */
function fcn_15_callIdExist($db_conn, $db_incident, $CallId, $logger)
{
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