<?php

/**
 * fcn_2TimeOver15Minutes
 *
 * @param  mixed $CreateDateTime
 * @return $send
 */
function fcn_20_DeltaTime($CreateDateTime)
{
    global $TimeAdjust;
    $Now = strtotime("now");
    $IncidentTime = strtotime($CreateDateTime);
    $delta = ($Now - $IncidentTime);

    return $delta;
}