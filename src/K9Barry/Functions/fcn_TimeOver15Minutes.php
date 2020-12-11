<?php

/**
 * fcn_TimeOver15Minutes
 *
 * @param  mixed $db_CreateDateTime
 * @return $send
 */
function fcn_TimeOver15Minutes($CreateDateTime)
{
    global $logger;
    $Now = strtotime("now");
    var_dump($Now);
    $IncidentTime = strtotime($CreateDateTime);
    var_dump($IncidentTime);
    $TimeAdjust = 900; // 15 minutes x 60 seconds
    $Difference = ($Now - $IncidentTime);
    var_dump($Difference);
    echo "\n";
    if ($Difference > $TimeAdjust) {
        $logger->info("Incident time is over 15 minutes ($Difference) - do not send to endpoints");
        return false;
    } else {
        $logger->info("Incident time is less than 15 minutes ($Difference) - Send to endpoints");
        return true;
    }
}
