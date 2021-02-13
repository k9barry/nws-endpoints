<?php

/**
 * fcn_closeConnection
 *
 * @param  mixed $db_conn
 * @param  mixed $logger
 * @return void
 */
function fcn_closeConnection($db_conn, $logger)
{
    $db_conn = null;
    $logger->info("Connection to database closed");
}
