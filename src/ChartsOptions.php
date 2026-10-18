<?php

namespace Waypoint\Charts;

use Closure;

/**
 * Settings for every chart the plugin draws.
 *
 * ```php
 * $app->configure(function (ChartsOptions $charts) {
 *     $charts->height = 280;
 *     $charts->format = fn (float $value, int $decimals): string => number_format($value, $decimals, ',', '.');
 * });
 * ```
 */
class ChartsOptions
{
    /** Default drawing width in CSS pixels; the SVG scales with its container via viewBox. */
    public int $width = 640;

    /** Default drawing height in CSS pixels. */
    public int $height = 320;

    /** Widest a bar may get; the rest of its slot stays air. */
    public int $maxBarWidth = 24;

    /**
     * Formats every value: axis ticks, labels, tooltips and the table. Default: number_format with '.' and ','.
     * The unit is appended after it.
     * @var (Closure(float $value, int $decimals): string)|null
     */
    public ?Closure $format = null;

    /** Render the data table (<details>) under every chart. Keep it: it is the accessible twin of the picture. */
    public bool $table = true;

    /** Text of the <summary> that opens the data table. */
    public string $tableLabel = 'Show data as table';

    /**
     * Series colors written into the SVG, in slot order; the plugin's stylesheet sets its own through CSS properties.
     *
     * @var list<string> Eight categorical colors for the light surface, in the fixed slot order (validated colorblind-safe).
     */
    public array $palette = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];

    /**
     * Series colors for dark mode inside a standalone SVG (a Chart returned from a route).
     *
     * @var list<string> The same eight hues stepped for the dark surface.
     */
    public array $paletteDark = ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'];
}
