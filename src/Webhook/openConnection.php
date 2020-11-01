<?php

namespace Webhook\Functions;

class openConnection
{
    /**
     * openConnection
     *
     * @param  mixed $db
     * @return $db_conn  DB connection
     */
    public function openConnection($db)
    {
        $db_conn = new PDO("sqlite:$db");
        $logger->info("Connection opened to database " . $db . "");
        print_r($db_conn);
        return $db_conn;
    }
}
