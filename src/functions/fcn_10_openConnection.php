<?php

/**
 * fcn_10_openConnection
 *
 * @param  string $db
 * @param  mixed $logger
 * @return $db_conn
 */
function fcn_10_openConnection($db, $logger)
{
    $db_conn = new PDO("sqlite:$db");
    $logger->info("Connection opened to database " . $db . "");
    return $db_conn;
}
