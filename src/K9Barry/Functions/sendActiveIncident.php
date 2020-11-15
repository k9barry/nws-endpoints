<?php

/**
 * sendActiveIncident
 *
 * @param  mixed $db_conn
 * @param  mixed $CfsTableName
 * @param  mixed $IncidentType
 * @return true | false
 */
function sendActiveIncident($db_conn, $CfsTableName, $IncidentType)
{
    global $logger;
    if (strpos($IncidentType, "|")) { // two incident types exist
        $type = explode("|", $IncidentType);
        $sql = "SELECT * FROM $CfsTableName WHERE cfstype LIKE '$type[0]' OR cfstype LIKE '$type[1]'";
    } else {
        $sql = "SELECT * FROM $CfsTableName WHERE cfstype LIKE '$IncidentType'";
    }
    $result = $db_conn->query($sql);
    $n = 0;
    foreach ($result as $row) {
        if ($row['fire'] == "Yes") {
            $n++;
        }
    }
    if ($n >= 1) {
        $logger->info("Incident " . $IncidentType ." is whitelisted");
        return true;
    } else {
        $logger->info("Incident " . $IncidentType . " is NOT whitelisted");
        return false;
    }
}
