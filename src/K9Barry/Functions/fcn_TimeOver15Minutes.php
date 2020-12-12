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
    $IncidentTime = strtotime($CreateDateTime);
    $TimeAdjust = 900; // 15 minutes x 60 seconds
    $delta = ($Now - $IncidentTime);
    if ($delta > $TimeAdjust) {
        $logger->info("Incident time is over 15 minutes ($delta) - do not send to endpoints");
    } else {
        $logger->info("Incident time is less than 15 minutes ($delta) - Send to endpoints");
    }
    return $delta;
}
