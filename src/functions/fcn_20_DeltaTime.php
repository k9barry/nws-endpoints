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
 * @throws InvalidArgumentException When timestamp format is invalid
 */
function fcn_20_deltaTime(string $CreateDateTime): int
{
    if (empty($CreateDateTime)) {
        throw new InvalidArgumentException("CreateDateTime cannot be empty");
    }
    
    $Now = time(); // More efficient than strtotime("now")
    $IncidentTime = strtotime($CreateDateTime);
    
    if ($IncidentTime === false) {
        throw new InvalidArgumentException("Invalid timestamp format: {$CreateDateTime}");
    }
    
    return max(0, $Now - $IncidentTime); // Ensure non-negative result
}
