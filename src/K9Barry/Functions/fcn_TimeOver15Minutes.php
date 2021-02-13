<?php

/**
 * fcn_TimeOver15Minutes
 *
 * @param  mixed $db_CreateDateTime
 * @return $send
 */
function fcn_TimeOver15Minutes($CreateDateTime)
{
    global $TimeAdjust;
    $Now = strtotime("now");
    $IncidentTime = strtotime($CreateDateTime);
    $delta = ($Now - $IncidentTime);

    return $delta;
}
