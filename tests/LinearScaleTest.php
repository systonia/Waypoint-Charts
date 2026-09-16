<?php

namespace Waypoint\Charts\Tests;

use PHPUnit\Framework\TestCase;
use Waypoint\Charts\Scale\LinearScale;

final class LinearScaleTest extends TestCase
{
    public function testNiceBoundsStepIn125AndIncludeZero(): void
    {
        $scale = LinearScale::nice(12.0, 165.0);
        $this->assertSame([0.0, 200.0, 50.0], [$scale->min, $scale->max, $scale->step]);
        $this->assertSame([0.0, 50.0, 100.0, 150.0, 200.0], $scale->ticks());
        $this->assertSame(0, $scale->decimals());
    }

    public function testNegativeAndFractionalRanges(): void
    {
        $scale = LinearScale::nice(-20.0, 40.0);
        $this->assertSame([-20.0, 40.0, 20.0], [$scale->min, $scale->max, $scale->step]);
        $this->assertSame([-20.0, 0.0, 20.0, 40.0], $scale->ticks());

        $small = LinearScale::nice(0.0, 2.3);
        $this->assertSame(0.5, $small->step);
        $this->assertSame(1, $small->decimals());
    }

    public function testAFlatSeriesStillGetsARange(): void
    {
        $scale = LinearScale::nice(3.0, 3.0);
        $this->assertGreaterThan($scale->min, $scale->max);
        $this->assertSame(0.0, $scale->min);
    }

    public function testPositionMapsLinearlyAndInverted(): void
    {
        $scale = LinearScale::nice(0.0, 100.0);
        $this->assertSame(200.0, $scale->position(0.0, 200.0, 0.0));
        $this->assertSame(100.0, $scale->position(50.0, 200.0, 0.0));
        $this->assertSame(0.0, $scale->position(100.0, 200.0, 0.0));
    }
}
