<?php

/**
 * fcn_20_deltaTime
 *
 * @param  mixed $CreateDateTime
 * @return $delta
 */
function fcn_20_deltaTime($CreateDateTime)
{
    global $TimeAdjust;
    $Now = strtotime("now");
    $IncidentTime = strtotime($CreateDateTime);
    $delta = ($Now - $IncidentTime);

    return $delta;
}
