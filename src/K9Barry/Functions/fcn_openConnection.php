<?php

/**
 * fcn_openConnection
 *
 * @param  mixed $db
 * @return $db_conn  DB connection
 */
function fcn_openConnection($db, $logger)
{
    $db_conn = new PDO("sqlite:$db");
    $logger->info("Connection opened to database " . $db . "");
    print_r($db_conn);
    return $db_conn;
}
