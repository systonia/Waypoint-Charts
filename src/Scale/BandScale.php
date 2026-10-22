<?php

namespace Waypoint\Charts\Scale;

/** Evenly spaced slots for categories along an axis, each with inner padding. */
final class BandScale
{
    private float $band;

    /**
     * @param int $count Number of slots.
     * @param float $start Position of the first slot's left edge.
     * @param float $width Width of all slots together.
     * @param float $padding Share of a slot (0 to 1) left empty, split between both sides.
     */
    public function __construct(int $count, private float $start, float $width, private float $padding = 0.2)
    {
        $this->band = $count > 0 ? $width / $count : $width;
    }

    /** Width of one slot. */
    public function band(): float
    {
        return $this->band;
    }

    /** Usable width inside a slot (the padding stays air). */
    public function inner(): float
    {
        return $this->band * (1 - $this->padding);
    }

    /** Left edge of slot $index. */
    public function start(int $index): float
    {
        return $this->start + $index * $this->band;
    }

    /** Left edge of the usable width inside slot $index. */
    public function innerStart(int $index): float
    {
        return $this->start($index) + $this->band * $this->padding / 2;
    }

    /** Center of slot $index. */
    public function center(int $index): float
    {
        return $this->start($index) + $this->band / 2;
    }
}
