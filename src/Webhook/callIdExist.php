<?php

namespace Webhook\Functions;

class callIdExist
{
    /**
     * callIdExist
     *
     * @param  mixed $db_conn
     * @param  mixed $db_incident
     * @param  mixed $CallId
     * @return $RowExists (true) if row exists
     */
    public function callIdExist($db_conn, $db_incident, $CallId)
    {
        $sql = "SELECT count(1) FROM $db_incident WHERE db_CallId = $CallId LIMIT 1";
        $result = $db_conn->query($sql);
        foreach ($result as $result) {
            $RowExists = $result[0];
            $logger->info("[callIdExist] Call id exists ");
        }
        return $RowExists;
    }
}
