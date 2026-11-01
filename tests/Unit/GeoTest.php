<?php

namespace Tests\Unit;

use App\Support\Geo;
use PHPUnit\Framework\TestCase;

class GeoTest extends TestCase
{
    public function test_it_validates_and_converts_coordinates_to_floats(): void
    {
        $this->assertSame([-6.2, 106.8], Geo::coordinate('-6.2', '106.8'));
        $this->assertSame([0.0, 0.0], Geo::coordinate(0, 0));
    }

    public function test_it_rejects_invalid_coordinates(): void
    {
        $this->assertNull(Geo::coordinate(null, 106.8));
        $this->assertNull(Geo::coordinate('bukan angka', 106.8));
        $this->assertNull(Geo::coordinate(-91, 106.8));
        $this->assertNull(Geo::coordinate(-6.2, 181));
    }

    public function test_it_calculates_haversine_distance_in_kilometers(): void
    {
        $this->assertSame(0.0, Geo::distanceKm(-6.2, 106.8, -6.2, 106.8));
        $this->assertEqualsWithDelta(111.19, Geo::distanceKm(0, 0, 0, 1), 0.01);
    }

    public function test_distance_is_symmetric(): void
    {
        $forward = Geo::distanceKm(-6.2, 106.8, -6.9175, 107.6191);
        $reverse = Geo::distanceKm(-6.9175, 107.6191, -6.2, 106.8);

        $this->assertEqualsWithDelta($forward, $reverse, 0.0001);
    }
}
