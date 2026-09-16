<?php

namespace Waypoint\Charts\Scale;

/** Evenly spaced slots for categories along an axis, each with inner padding. */
final class BandScale
{
    private float $band;

    public function __construct(private int $count, private float $start, float $width, private float $padding = 0.2)
    {
        $this->band = $count > 0 ? $width / $count : $width;
    }

    public function band(): float
    {
        return $this->band;
    }

    /** Usable width inside a slot (the padding stays air). */
    public function inner(): float
    {
        return $this->band * (1 - $this->padding);
    }

    public function start(int $index): float
    {
        return $this->start + $index * $this->band;
    }

    public function innerStart(int $index): float
    {
        return $this->start($index) + $this->band * $this->padding / 2;
    }

    public function center(int $index): float
    {
        return $this->start($index) + $this->band / 2;
    }

    public function count(): int
    {
        return $this->count;
    }
}
