<?php

/**
 * closeConnection
 *
 * @param  mixed $db_conn
 * @return void
 */
function closeConnection($db_conn)
{
    global $logger;
    $db_conn = null;
    $logger->info("Connection to database closed");
}
