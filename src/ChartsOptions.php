<?php

namespace Waypoint\Charts;

use Closure;

/** Configure via App::configure(function (ChartsOptions $opts) { ... }). */
class ChartsOptions
{
    /** Default drawing size in CSS pixels; the SVG scales with its container via viewBox. */
    public int $width = 640;
    public int $height = 320;

    /** Widest a bar may get; the rest of its slot stays air. */
    public int $maxBarWidth = 24;

    /**
     * Formats a value. Default: number_format with '.' and ','.
     * @var (Closure(float $value, int $decimals): string)|null
     */
    public ?Closure $format = null;

    /** Render the data table (<details>) under every chart. Keep it: it is the accessible twin of the picture. */
    public bool $table = true;

    /** Text of the <summary> that opens the data table. */
    public string $tableLabel = 'Show data as table';

    /** @var list<string> Eight categorical colors for the light surface, in the fixed slot order (validated colorblind-safe). */
    public array $palette = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];

    /** @var list<string> The same eight hues stepped for the dark surface. */
    public array $paletteDark = ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'];
}
