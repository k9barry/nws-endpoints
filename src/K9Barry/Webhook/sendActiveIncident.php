<?php

namespace K9Barry;

class sendActiveIncident
{
    /**
     * sendActiveIncident
     *
     * @param  mixed $db_conn
     * @param  mixed $db_nwsCfsTableName
     * @param  mixed $IncidentType
     * @return true | false
     */
    public function sendActiveIncident($db_conn, $db_nwsCfsTableName, $IncidentType)
    {
        if (strpos($IncidentType, "|")) { // two incident types exist
            $type = explode("|", $IncidentType);
            $sql = "SELECT * FROM $db_nwsCfsTableName WHERE cfstype LIKE '$type[0]' OR cfstype LIKE '$type[1]'";
        } else {
            $sql = "SELECT * FROM $db_nwsCfsTableName WHERE cfstype LIKE '$IncidentType'";
        }
        $result = $db_conn->query($sql);
        $n = 0;
        foreach ($result as $row) {
            if ($row['active'] == "Yes") {
                $n++;
            }
        }
        if ($n >= 1) {
            $logger->info("Incident is whitelisted");
            return true;
        } else {
            $logger->info("Incident is NOT whitelisted");
            return false;
        }
    }
}
