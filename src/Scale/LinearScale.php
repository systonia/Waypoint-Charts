<?php

namespace Waypoint\Charts\Scale;

/** A value axis with "nice" bounds and ticks stepping in 1, 2, 5 × 10^n. */
final class LinearScale
{
    private function __construct(
        /** Lowest tick, the bottom of the axis. */
        public readonly float $min,
        /** Highest tick, the top of the axis. */
        public readonly float $max,
        /** Distance between two ticks. */
        public readonly float $step,
    ) {
    }

    /** Bounds around [$lo, $hi] on clean tick values, about $ticks intervals; $includeZero anchors the baseline. */
    public static function nice(float $lo, float $hi, int $ticks = 5, bool $includeZero = true): self
    {
        if ($includeZero) {
            $lo = min($lo, 0.0);
            $hi = max($hi, 0.0);
        }
        if ($hi <= $lo) {
            $hi = $lo + 1.0;
        }
        $raw = ($hi - $lo) / max(1, $ticks);
        $magnitude = 10 ** floor(log10($raw));
        $normalized = $raw / $magnitude;
        $step = ($normalized <= 1 ? 1 : ($normalized <= 2 ? 2 : ($normalized <= 5 ? 5 : 10))) * $magnitude;
        $min = floor($lo / $step) * $step;
        $max = ceil($hi / $step) * $step;
        if ($max <= $min) {
            $max = $min + $step;
        }
        return new self($min, $max, $step);
    }

    /** @return list<float> Every tick from min to max inclusive. */
    public function ticks(): array
    {
        $ticks = [];
        $count = (int) round(($this->max - $this->min) / $this->step);
        for ($i = 0; $i <= $count; $i++) {
            $ticks[] = $this->min + $i * $this->step;
        }
        return $ticks;
    }

    /** Pixel position of $value between $from (min) and $to (max). */
    public function position(float $value, float $from, float $to): float
    {
        return $from + ($value - $this->min) / ($this->max - $this->min) * ($to - $from);
    }

    /** Decimals needed to print the ticks without rounding away the step. */
    public function decimals(): int
    {
        $decimals = 0;
        $step = $this->step;
        while ($decimals < 6 && abs($step - round($step)) > 1e-9) {
            $step *= 10;
            $decimals++;
        }
        return $decimals;
    }
}
