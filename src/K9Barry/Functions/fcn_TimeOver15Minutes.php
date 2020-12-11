<?php

/**
 * fcn_TimeOver15Minutes
 *
 * @param  mixed $db_CreateDateTime
 * @return $send
 */
function fcn_TimeOver15Minutes(2020-12-11 15:48:50Z)
{
    $Now = strtotime("now");
    var_dump($Now);
    $IncidentTime = strtotime($db_CreateDateTime);
    var_dump($IncidentTime);
    $TimeAdjust = 900); // 15 minutes x 60 seconds
    if (($Now - $IncidentTime) >= $TimeAdjust) {
        $logger->info("Incident time is over 15 minutes - do not send to endpoints");
        return false;
    } else {
        $logger->info("Incident time is less than 15 minutes - Send to endpoints");
        return true;
    }
}
