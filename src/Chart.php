<?php

namespace Waypoint\Charts;

use InvalidArgumentException;

/**
 * A bar or line chart: labels, up to eight series and how to show them, built fluently.
 *
 * ```php
 * $chart = Chart::bar()
 *     ->title('Sold this week')
 *     ->labels(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
 *     ->series('Croissants', [84, 76, 91, 88, 102, 140])
 *     ->series('Pretzels', [60, 58, 64, 61, 75, 96]);
 * ```
 *
 * Return it from a route to get an image/svg+xml response, or pass it to
 * `$this->chart($chart)` inside a view. SvgRenderer draws it.
 */
final class Chart
{
    /** Type of a bar chart, for fromRows() and type(). */
    public const BAR = 'bar';

    /** Type of a line chart, for fromRows() and type(). */
    public const LINE = 'line';

    private ?string $title = null;
    private ?string $description = null;

    /** @var list<string> */
    private array $labels = [];

    /** @var list<array{name: string, values: list<float|null>}> */
    private array $series = [];

    private string $unit = '';
    private int $decimals = 0;
    private bool $stacked = false;
    private bool $markers = true;
    private bool $table = true;
    private ?int $width = null;
    private ?int $height = null;

    private function __construct(private string $type)
    {
    }

    /** A new bar chart: one bar per series at every label, grouped side by side or stacked(). */
    public static function bar(): self
    {
        return new self(self::BAR);
    }

    /** A new line chart: one line per series through its values, with markers by default. */
    public static function line(): self
    {
        return new self(self::LINE);
    }

    /**
     * A chart from query rows: labels in order of first appearance, one series per distinct
     * value of the $series column.
     *
     * Values of the same label and series add up; a label a series has no row for stays a gap
     * (null). A value that is not numeric counts as 0.
     *
     * @param iterable<array<string, mixed>|object> $rows Arrays or objects with the named keys or properties.
     * @param string $x Column with the label (the x position).
     * @param string $y Column with the value.
     * @param string|null $series Column that splits rows into series; null for one series with an empty name.
     * @param string $type Chart::BAR or Chart::LINE.
     */
    public static function fromRows(iterable $rows, string $x, string $y, ?string $series = null, string $type = self::BAR): self
    {
        $chart = new self($type);
        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $get = fn(string $key): mixed => is_array($row) ? ($row[$key] ?? null) : ($row->{$key} ?? null);
            $label = (string) self::scalar($get($x));
            $name = $series === null ? '' : (string) self::scalar($get($series));
            $value = $get($y);
            if (!in_array($label, $labels, true)) {
                $labels[] = $label;
            }
            $data[$name][$label] = ($data[$name][$label] ?? 0.0) + (is_numeric($value) ? (float) $value : 0.0);
        }
        $chart->labels($labels);
        foreach ($data as $name => $byLabel) {
            $chart->series((string) $name, array_map(fn(string $l): ?float => $byLabel[$l] ?? null, $labels));
        }
        return $chart;
    }

    /** Caption above the chart, the SVG's <title> and the table's <caption>. */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /** A sentence for screen readers describing what the chart shows; generated from the data when omitted. */
    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /** @param list<string> $labels One per x position, shared by every series. */
    public function labels(array $labels): self
    {
        $this->labels = array_map('strval', $labels);
        return $this;
    }

    /** @param list<float|int|null> $values One per label; null leaves a gap. At most eight series. */
    public function series(string $name, array $values): self
    {
        if (count($this->series) >= 8) {
            throw new InvalidArgumentException('A chart takes at most eight series; fold the rest into "Other" or draw a second chart.');
        }
        $this->series[] = ['name' => $name, 'values' => array_map(fn(float|int|null $v): ?float => $v === null ? null : (float) $v, $values)];
        return $this;
    }

    /** Unit appended to values in labels, tooltips and the table ('€', '%'), and how many decimals to show. */
    public function unit(string $unit, int $decimals = 0): self
    {
        $this->unit = $unit;
        $this->decimals = max(0, $decimals);
        return $this;
    }

    /** Bars only: stack the series instead of grouping them side by side. */
    public function stacked(bool $stacked = true): self
    {
        $this->stacked = $stacked;
        return $this;
    }

    /** Lines only: draw a marker at every point. */
    public function markers(bool $markers = true): self
    {
        $this->markers = $markers;
        return $this;
    }

    /** Show the data table under this chart; false hides it even when ChartsOptions::$table is on. */
    public function withTable(bool $table = true): self
    {
        $this->table = $table;
        return $this;
    }

    /** Drawing size in CSS pixels for this chart, at least 120 × 80; ChartsOptions' size otherwise. */
    public function size(int $width, int $height): self
    {
        $this->width = max(120, $width);
        $this->height = max(80, $height);
        return $this;
    }

    /** Chart::BAR or Chart::LINE. */
    public function type(): string
    {
        return $this->type;
    }

    /** The title, or null when none was set. */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /** The screen-reader sentence, or null when the renderer generates one. */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * The labels, one per x position.
     *
     * @return list<string>
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * The series in the order they were added, values as floats and gaps as null.
     *
     * @return list<array{name: string, values: list<float|null>}>
     */
    public function getSeries(): array
    {
        return $this->series;
    }

    /** The unit from unit(), '' when none. */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /** Decimals values are shown with. */
    public function getDecimals(): int
    {
        return $this->decimals;
    }

    /** True for a bar chart with stacked(); a line chart never stacks. */
    public function isStacked(): bool
    {
        return $this->stacked && $this->type === self::BAR;
    }

    /** Whether a line chart draws a marker at every point. */
    public function hasMarkers(): bool
    {
        return $this->markers;
    }

    /** Whether this chart asks for its data table (ChartsOptions::$table can still turn it off). */
    public function hasTable(): bool
    {
        return $this->table;
    }

    /** Width from size(), or null for ChartsOptions::$width. */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /** Height from size(), or null for ChartsOptions::$height. */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    private static function scalar(mixed $value): string|int|float
    {
        return is_scalar($value) || $value instanceof \Stringable ? (is_bool($value) ? (int) $value : (is_string($value) || is_int($value) || is_float($value) ? $value : (string) $value)) : '';
    }
}
