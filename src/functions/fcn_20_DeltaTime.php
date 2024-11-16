<?php

/**
 * fcn_20_DeltaTime
 *
 * @param  mixed $CreateDateTime
 * @return $delta
 */
function fcn_20_DeltaTime($CreateDateTime)
{
    global $TimeAdjust;
    $Now = strtotime("now");
    $IncidentTime = strtotime($CreateDateTime);
    $delta = ($Now - $IncidentTime);

    return $delta;
}