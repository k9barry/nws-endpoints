<?php

/**
 * fcn_20_deltaTime
 * 
 * Calculates the time difference between incident creation and current time.
 * Used to determine how fresh an incident is for notification purposes and
 * to filter out incidents that are too old to be relevant.
 *
 * @param string $CreateDateTime Incident creation timestamp from New World CAD
 * @return int Time difference in seconds between now and incident creation
 */
function fcn_20_deltaTime(string $CreateDateTime): int
{
    global $TimeAdjust;
    $Now = strtotime("now");
    $IncidentTime = strtotime($CreateDateTime);
    return ($Now - $IncidentTime);
}
