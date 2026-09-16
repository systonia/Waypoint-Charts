<?php

namespace Waypoint\Charts;

use InvalidArgumentException;

/**
 * What to draw, built fluently and rendered by SvgRenderer:
 *
 *   Chart::bar()->title('Revenue 2026')->labels(['Jan', 'Feb'])->series('Domestic', [120, 140])->unit('€')
 *
 * Return it from a route to get an image/svg+xml response, or pass it to
 * `$this->chart($chart)` inside a view.
 */
final class Chart
{
    public const BAR = 'bar';
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

    public static function bar(): self
    {
        return new self(self::BAR);
    }

    public static function line(): self
    {
        return new self(self::LINE);
    }

    /**
     * From query rows: $x names the label column, $y the value column, $series (optional) the column
     * that splits rows into series. Values of the same label and series add up.
     *
     * @param iterable<array<string, mixed>|object> $rows
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

    public function withTable(bool $table = true): self
    {
        $this->table = $table;
        return $this;
    }

    public function size(int $width, int $height): self
    {
        $this->width = max(120, $width);
        $this->height = max(80, $height);
        return $this;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @return list<string> */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /** @return list<array{name: string, values: list<float|null>}> */
    public function getSeries(): array
    {
        return $this->series;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function isStacked(): bool
    {
        return $this->stacked && $this->type === self::BAR;
    }

    public function hasMarkers(): bool
    {
        return $this->markers;
    }

    public function hasTable(): bool
    {
        return $this->table;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    private static function scalar(mixed $value): string|int|float
    {
        return is_scalar($value) || $value instanceof \Stringable ? (is_bool($value) ? (int) $value : (is_string($value) || is_int($value) || is_float($value) ? $value : (string) $value)) : '';
    }
}
