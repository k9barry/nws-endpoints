<?php

/**
 * fcn_20_deltaTime
 * 
 * Calculates the time difference between incident creation and current time.
 * Incoming CreateDateTime values like "2026-01-14T09:04:22" are provided
 * in America/Indiana/Indianapolis local time (no timezone). This function
 * will treat timezone-less timestamps as that zone (or the provided one), convert to UTC, then
 * subtract from server UTC "now" to compute the delta in seconds.
 *
 * @param string $CreateDateTime Incident creation timestamp from New World CAD
 * @param string|null $sourceTimeZone Optional IANA timezone string for incoming timestamps
 * @return int Time difference in seconds between now (UTC) and incident creation
 * @throws InvalidArgumentException When timestamp format is invalid
 */
function fcn_20_deltaTime(string $CreateDateTime, ?string $sourceTimeZone = null): int
{
    if (empty($CreateDateTime)) {
        throw new InvalidArgumentException("CreateDateTime cannot be empty");
    }

    // Determine the source timezone to treat timezone-less timestamps as
    $sourceTzName = $sourceTimeZone ?? 'America/Indiana/Indianapolis';

    try {
        $localTz = new DateTimeZone($sourceTzName);
    } catch (Exception $e) {
        // Fallback to a sensible default if the configured timezone is invalid
        $localTz = new DateTimeZone('America/Indiana/Indianapolis');
    }

    $utcTz = new DateTimeZone('UTC');

    try {
        // If $CreateDateTime contains an explicit timezone/offset, DateTimeImmutable
        // will respect it. If not, the provided $localTz is applied.
        $incidentDt = new DateTimeImmutable($CreateDateTime, $localTz);
    } catch (Exception $e) {
        throw new InvalidArgumentException("Invalid timestamp format: {$CreateDateTime}");
    }

    // Convert incident time to UTC
    $incidentUtc = $incidentDt->setTimezone($utcTz);

    // Current server time in UTC
    $nowUtc = new DateTimeImmutable('now', $utcTz);

    $delta = $nowUtc->getTimestamp() - $incidentUtc->getTimestamp();

    return max(0, $delta);
}
