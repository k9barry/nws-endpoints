<?php

namespace K9Barry\Webhook;

class closeConnection
{
    /**
     * closeConnection
     *
     * @param  mixed $db_conn
     * @return void
     */
    public function closeConnection($db_conn)
    {
        global $logger;
        $db_conn = null;
        $logger->info("Connection to database closed");
    }
}
