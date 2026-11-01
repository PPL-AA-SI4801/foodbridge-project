<?php

namespace App\Support;

final class Geo
{
    /**
     * Earth's mean radius in kilometers.
     */
    public const EARTH_RADIUS_KM = 6371.0;

    /**
     * Validate a latitude/longitude pair and return it as floats.
     *
     * @return array{0: float, 1: float}|null
     */
    public static function coordinate(mixed $lat, mixed $lng): ?array
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $latitude = (float) $lat;
        $longitude = (float) $lng;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return [$latitude, $longitude];
    }

    /**
     * Calculate the great-circle distance between two coordinates in kilometers.
     */
    public static function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * asin(sqrt(min(1.0, $a)));
    }
}
