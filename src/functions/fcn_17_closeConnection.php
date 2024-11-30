<?php

/**
 * fcn_17_closeConnection
 *
 * @param  mixed $db_conn
 * @param  mixed $logger
 * @return void
 */
function fcn_17_closeConnection(mixed $db_conn, mixed $logger): void
{
    $db_conn = null;
    $logger->info("Connection to database closed");
}
