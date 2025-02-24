<?php

/**
 * fcn_20_deltaTime
 *
 * @param mixed $CreateDateTime
 * @return int $delta
 */
function fcn_20_deltaTime(mixed $CreateDateTime): int
{
    global $TimeAdjust;
    $Now = strtotime("now");
    $IncidentTime = strtotime($CreateDateTime);
    return ($Now - $IncidentTime);
}
