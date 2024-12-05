<?php

/**
 * fcn_15_callIdExist
 *
 * @param mixed $db_conn
 * @param string $db_incident
 * @param int $CallId
 * @param mixed $logger
 * @return mixed $RowExists
 */
function fcn_15_callIdExist(mixed $db_conn, string $db_incident, $CallId, mixed $logger): mixed
{
    $sql = "SELECT count(1) FROM $db_incident WHERE db_CallId = $CallId LIMIT 1";
    $result = $db_conn->query($sql);
    foreach ($result as $result) {
    }
    if ($result[0]) {
        $logger->info("Call ID exists in database");
    } else {
        $logger->info("Call ID does not exist in database");
    }
    return $result[0];
}
