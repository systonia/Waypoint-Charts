<?php

namespace Waypoint\Charts;

use Waypoint\Charts\Scale\{BandScale, LinearScale};

/**
 * Draws a Chart as SVG. render() wraps it in a <figure> with caption, legend
 * and the data table (the accessible twin of the picture); renderStandalone()
 * returns a bare <svg> for image/svg+xml responses and <img> tags.
 *
 * Marks carry their colors as presentation attributes (so the SVG is readable
 * without the stylesheet) and the plugin's CSS overrides them for dark mode.
 */
final class SvgRenderer
{
    private const CHAR_WIDTH = 7.0;   // estimated px per character at 12px
    private const GAP = 2;            // surface gap between touching marks
    private const RADIUS = 4;         // rounded data end of a bar
    private const MARKER = 4;         // marker radius
    private const MAX_END_LABELS = 4; // line end labels only up to this many series

    private const INK = ['muted' => '#898781', 'grid' => '#e1e0d9', 'axis' => '#c3c2b7', 'surface' => '#fcfcfb', 'text' => '#52514e'];
    private const INK_DARK = ['muted' => '#898781', 'grid' => '#2c2c2a', 'axis' => '#383835', 'surface' => '#1a1a19', 'text' => '#c3c2b7'];

    public function __construct(private ChartsOptions $options)
    {
    }

    public function render(Chart $chart): string
    {
        $series = $chart->getSeries();
        $id = self::id($chart);
        $html = sprintf('<figure class="wp-chart wp-chart-%s" wp-chart data-series="%d">', $chart->type(), count($series));
        if ($chart->getTitle() !== null) {
            $html .= '<figcaption class="wp-chart-title">' . self::e($chart->getTitle()) . '</figcaption>';
        }
        $html .= '<div class="wp-chart-plot">' . $this->svg($chart, $id, false) . '</div>';
        if (count($series) >= 2) {
            $html .= $this->legend($chart);
        }
        if ($chart->hasTable() && $this->options->table) {
            $html .= $this->table($chart);
        }
        return $html . '</figure>';
    }

    public function renderStandalone(Chart $chart): string
    {
        return $this->svg($chart, self::id($chart), true);
    }

    private function svg(Chart $chart, string $id, bool $standalone): string
    {
        $labels = $chart->getLabels();
        $series = $chart->getSeries();
        $n = count($labels);
        $w = $chart->getWidth() ?? $this->options->width;
        $h = $chart->getHeight() ?? $this->options->height;
        $isBar = $chart->type() === Chart::BAR;
        $stacked = $chart->isStacked();
        $unit = $chart->getUnit();
        $decimals = $chart->getDecimals();

        [$lo, $hi] = $this->extent($series, $stacked, $n);
        $scale = LinearScale::nice($lo, $hi, 5);
        $tickDecimals = max($decimals, $scale->decimals());
        $ticks = $scale->ticks();
        $tickLabels = array_map(fn(float $t): string => $this->fmt($t, $tickDecimals), $ticks);

        $directLabel = $isBar && !$stacked && count($series) === 1;
        $endLabels = !$isBar && count($series) <= self::MAX_END_LABELS && count($series) > 0;
        $left = 10 + (int) ceil(max(array_map('strlen', $tickLabels ?: ['0'])) * self::CHAR_WIDTH);
        $right = 16;
        if ($endLabels) {
            $longest = 0;
            foreach ($series as $s) {
                $last = self::lastValue($s['values']);
                $longest = max($longest, $last === null ? 0 : strlen($this->fmt($last, $decimals, $unit)));
            }
            $right = 12 + (int) ceil($longest * self::CHAR_WIDTH);
        }
        $top = $directLabel ? 24 : 12;
        $bottom = 30;
        $x0 = $left;
        $x1 = $w - $right;
        $y0 = $top;
        $y1 = $h - $bottom;
        $y = fn(float $v): float => $scale->position($v, $y1, $y0);
        $band = new BandScale($n, $x0, $x1 - $x0, $isBar ? 0.3 : 0.0);

        $ink = self::INK;
        $out = [];

        // grid + y ticks
        foreach ($ticks as $i => $tick) {
            $ty = $y($tick);
            $out[] = sprintf('<line class="wp-chart-grid" x1="%s" x2="%s" y1="%s" y2="%s" stroke="%s" stroke-width="1"/>', self::n($x0), self::n($x1), self::n($ty), self::n($ty), $ink['grid']);
            $out[] = sprintf('<text class="wp-chart-tick" x="%s" y="%s" text-anchor="end" fill="%s">%s</text>', self::n($x0 - 6), self::n($ty + 4), $ink['muted'], self::e($tickLabels[$i]));
        }
        $baseline = $scale->min <= 0 && $scale->max >= 0 ? $y(0.0) : $y1;
        $out[] = sprintf('<line class="wp-chart-axis" x1="%s" x2="%s" y1="%s" y2="%s" stroke="%s" stroke-width="1"/>', self::n($x0), self::n($x1), self::n($baseline), self::n($baseline), $ink['axis']);

        // x labels, thinned so they never overlap
        $longestLabel = max(array_map('strlen', $labels ?: ['']));
        $every = $n > 0 ? max(1, (int) ceil($n * ($longestLabel * self::CHAR_WIDTH + 8) / max(1, $x1 - $x0))) : 1;
        foreach ($labels as $i => $label) {
            if ($i % $every === 0) {
                $out[] = sprintf('<text class="wp-chart-tick" x="%s" y="%s" text-anchor="middle" fill="%s">%s</text>', self::n($band->center($i)), self::n($y1 + 18), $ink['muted'], self::e($label));
            }
        }

        $out[] = $isBar ? $this->bars($chart, $band, $y, $baseline, $directLabel) : $this->lines($chart, $band, $y, $endLabels);

        // one hit area per x position: keyboard-reachable, with a native <title> until the script takes over
        foreach ($labels as $i => $label) {
            $parts = [];
            foreach ($series as $s) {
                $v = $s['values'][$i] ?? null;
                $parts[] = ($s['name'] !== '' ? $s['name'] . ' ' : '') . ($v === null ? '–' : $this->fmt($v, $decimals, $unit));
            }
            $summary = $label . ': ' . implode(', ', $parts);
            $out[] = sprintf(
                '<rect class="wp-chart-hit" wp-chart-hit x="%s" y="%s" width="%s" height="%s" fill="transparent" tabindex="0" data-index="%d" aria-label="%s"><title>%s</title></rect>',
                self::n($band->start($i)),
                self::n($y0),
                self::n($band->band()),
                self::n($y1 - $y0),
                $i,
                self::e($summary),
                self::e($summary)
            );
        }
        if (!$isBar) {
            $out[] = sprintf('<line class="wp-chart-crosshair" x1="0" x2="0" y1="%s" y2="%s" stroke="%s" stroke-width="1"/>', self::n($y0), self::n($y1), $ink['axis']);
        }

        $title = $chart->getTitle() ?? 'Chart';
        $desc = $chart->getDescription() ?? $this->describe($chart);
        $style = $standalone ? $this->standaloneStyle() : '';
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" class="wp-chart-svg" viewBox="0 0 %d %d" width="%d" height="%d" role="img" aria-labelledby="%s-title %s-desc" font-family="system-ui, -apple-system, \'Segoe UI\', sans-serif" font-size="12">%s<title id="%s-title">%s</title><desc id="%s-desc">%s</desc>%s</svg>',
            $w, $h, $w, $h, $id, $id, $style, $id, self::e($title), $id, self::e($desc), implode('', $out)
        );
    }

    /** @param callable(float): float $y */
    private function bars(Chart $chart, BandScale $band, callable $y, float $baseline, bool $directLabel): string
    {
        $series = $chart->getSeries();
        $n = count($chart->getLabels());
        $stacked = $chart->isStacked();
        $groups = $stacked ? 1 : count($series);
        $barWidth = min((float) $this->options->maxBarWidth, ($band->inner() - self::GAP * ($groups - 1)) / max(1, $groups));
        $groupWidth = $barWidth * $groups + self::GAP * ($groups - 1);
        $out = [];
        $maxIndex = null;
        $maxValue = null;

        for ($i = 0; $i < $n; $i++) {
            $gx = $band->center($i) - $groupWidth / 2;
            $positive = 0.0;
            $negative = 0.0;
            foreach ($series as $j => $s) {
                $v = $s['values'][$i] ?? null;
                if ($v === null) {
                    continue;
                }
                if ($stacked) {
                    $from = $v >= 0 ? $positive : $negative;
                    $to = $from + $v;
                    $v >= 0 ? $positive = $to : $negative = $to;
                    $isOutermost = self::isOutermost($series, $i, $j, $v >= 0);
                    $yFrom = $y($from) + ($from !== 0.0 ? ($v >= 0 ? -self::GAP : self::GAP) : 0.0);
                    $yTo = $y($to);
                    $x = $gx;
                } else {
                    $yFrom = $baseline;
                    $yTo = $y($v);
                    $isOutermost = true;
                    $x = $gx + $j * ($barWidth + self::GAP);
                }
                $top = min($yFrom, $yTo);
                $height = max(0.0, abs($yFrom - $yTo));
                $out[] = sprintf(
                    '<path class="wp-chart-mark wp-chart-bar wp-chart-series-%d" d="%s" fill="%s" data-index="%d" data-series="%d"/>',
                    $j + 1,
                    self::barPath($x, $top, $barWidth, $height, $isOutermost && $v >= 0, $isOutermost && $v < 0),
                    $this->color($j),
                    $i,
                    $j
                );
                if ($directLabel && ($maxValue === null || $v > $maxValue)) {
                    $maxValue = $v;
                    $maxIndex = $i;
                }
            }
        }

        if ($directLabel && $maxIndex !== null && $maxValue !== null) {
            $out[] = sprintf(
                '<text class="wp-chart-label" x="%s" y="%s" text-anchor="middle" fill="%s">%s</text>',
                self::n($band->center($maxIndex)),
                self::n($y($maxValue) - 6),
                self::INK['text'],
                self::e($this->fmt($maxValue, $chart->getDecimals(), $chart->getUnit()))
            );
        }
        return implode('', $out);
    }

    /** @param callable(float): float $y */
    private function lines(Chart $chart, BandScale $band, callable $y, bool $endLabels): string
    {
        $out = [];
        $placed = [];
        foreach ($chart->getSeries() as $j => $s) {
            $d = '';
            $pen = false;
            $markers = '';
            $last = null;
            foreach ($s['values'] as $i => $v) {
                if ($v === null) {
                    $pen = false;
                    continue;
                }
                $px = $band->center($i);
                $py = $y($v);
                $d .= ($pen ? 'L' : 'M') . self::n($px) . ',' . self::n($py);
                $pen = true;
                $last = [$px, $py, $v];
                if ($chart->hasMarkers()) {
                    $markers .= sprintf('<circle class="wp-chart-marker wp-chart-series-%d" cx="%s" cy="%s" r="%d" fill="%s" stroke="%s" stroke-width="2" data-index="%d" data-series="%d"/>', $j + 1, self::n($px), self::n($py), self::MARKER, $this->color($j), self::INK['surface'], $i, $j);
                }
            }
            $out[] = sprintf('<path class="wp-chart-mark wp-chart-line wp-chart-series-%d" d="%s" fill="none" stroke="%s" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" data-series="%d"/>', $j + 1, $d, $this->color($j), $j);
            $out[] = $markers;
            if ($endLabels && $last !== null) {
                $ly = $last[1];
                $collides = false;
                foreach ($placed as $py) {
                    if (abs($py - $ly) < 12) {
                        $collides = true;
                    }
                }
                if (!$collides) {
                    $placed[] = $ly;
                    $out[] = sprintf('<text class="wp-chart-label" x="%s" y="%s" fill="%s">%s</text>', self::n($last[0] + self::MARKER + 6), self::n($ly + 4), self::INK['text'], self::e($this->fmt($last[2], $chart->getDecimals(), $chart->getUnit())));
                }
            }
        }
        return implode('', $out);
    }

    private function legend(Chart $chart): string
    {
        $items = '';
        foreach ($chart->getSeries() as $j => $s) {
            $color = $this->color($j);
            $key = $chart->type() === Chart::BAR
                ? sprintf('<svg class="wp-chart-key" width="12" height="12" aria-hidden="true"><rect width="12" height="12" rx="2" class="wp-chart-series-%d" fill="%s"/></svg>', $j + 1, $color)
                : sprintf('<svg class="wp-chart-key" width="18" height="10" aria-hidden="true"><line x1="0" y1="5" x2="18" y2="5" class="wp-chart-series-%d" stroke="%s" stroke-width="2"/><circle cx="9" cy="5" r="3" class="wp-chart-series-%d" fill="%s"/></svg>', $j + 1, $color, $j + 1, $color);
            $items .= '<li>' . $key . '<span>' . self::e($s['name']) . '</span></li>';
        }
        return '<ul class="wp-chart-legend">' . $items . '</ul>';
    }

    private function table(Chart $chart): string
    {
        $head = '<th scope="col"></th>';
        foreach ($chart->getSeries() as $s) {
            $head .= '<th scope="col">' . self::e($s['name']) . '</th>';
        }
        $rows = '';
        foreach ($chart->getLabels() as $i => $label) {
            $cells = '';
            foreach ($chart->getSeries() as $s) {
                $v = $s['values'][$i] ?? null;
                $cells .= '<td>' . ($v === null ? '–' : self::e($this->fmt($v, $chart->getDecimals(), $chart->getUnit()))) . '</td>';
            }
            $rows .= '<tr><th scope="row">' . self::e($label) . '</th>' . $cells . '</tr>';
        }
        $caption = $chart->getTitle() !== null ? '<caption>' . self::e($chart->getTitle()) . '</caption>' : '';
        return '<details class="wp-chart-details"><summary>' . self::e($this->options->tableLabel) . '</summary>'
            . '<table class="wp-chart-table">' . $caption . '<thead><tr>' . $head . '</tr></thead><tbody>' . $rows . '</tbody></table></details>';
    }

    /** A sentence for the <desc>: range of categories, the series, and the highest value. */
    private function describe(Chart $chart): string
    {
        $labels = $chart->getLabels();
        $series = $chart->getSeries();
        $max = null;
        foreach ($series as $s) {
            foreach ($s['values'] as $i => $v) {
                if ($v !== null && ($max === null || $v > $max[0])) {
                    $max = [$v, $labels[$i] ?? '', $s['name']];
                }
            }
        }
        $text = sprintf('%s chart with %d categories from %s to %s', $chart->type() === Chart::BAR ? 'Bar' : 'Line', count($labels), $labels[0] ?? '', $labels[count($labels) - 1] ?? '');
        $names = array_filter(array_map(fn(array $s): string => $s['name'], $series));
        if ($names !== []) {
            $text .= '; series: ' . implode(', ', $names);
        }
        if ($max !== null) {
            $text .= sprintf('. Highest value %s at %s%s', $this->fmt($max[0], $chart->getDecimals(), $chart->getUnit()), $max[1], $max[2] !== '' ? " ($max[2])" : '');
        }
        return $text . '.';
    }

    /**
     * @param list<array{name: string, values: list<float|null>}> $series
     * @return array{0: float, 1: float}
     */
    private function extent(array $series, bool $stacked, int $n): array
    {
        $lo = 0.0;
        $hi = 0.0;
        if ($stacked) {
            for ($i = 0; $i < $n; $i++) {
                $pos = 0.0;
                $neg = 0.0;
                foreach ($series as $s) {
                    $v = $s['values'][$i] ?? null;
                    if ($v !== null) {
                        $v >= 0 ? $pos += $v : $neg += $v;
                    }
                }
                $hi = max($hi, $pos);
                $lo = min($lo, $neg);
            }
            return [$lo, $hi];
        }
        foreach ($series as $s) {
            foreach ($s['values'] as $v) {
                if ($v !== null) {
                    $hi = max($hi, $v);
                    $lo = min($lo, $v);
                }
            }
        }
        return [$lo, $hi];
    }

    /** @param list<array{name: string, values: list<float|null>}> $series */
    private static function isOutermost(array $series, int $index, int $seriesIndex, bool $positive): bool
    {
        foreach ($series as $j => $s) {
            $v = $s['values'][$index] ?? null;
            if ($j > $seriesIndex && $v !== null && ($v >= 0) === $positive) {
                return false;
            }
        }
        return true;
    }

    /** A bar with a 4px rounded data end and a square baseline end. */
    private static function barPath(float $x, float $top, float $width, float $height, bool $roundTop, bool $roundBottom): string
    {
        $r = min((float) self::RADIUS, $width / 2, $height / 2);
        $bottom = $top + $height;
        $rt = $roundTop ? $r : 0.0;
        $rb = $roundBottom ? $r : 0.0;
        $corner = fn(float $cx, float $cy, float $ex, float $ey, bool $round): string => $round
            ? sprintf('Q%s,%s %s,%s', self::n($cx), self::n($cy), self::n($ex), self::n($ey))
            : '';
        return sprintf('M%s,%s', self::n($x), self::n($top + $rt))
            . $corner($x, $top, $x + $rt, $top, $roundTop)
            . sprintf('L%s,%s', self::n($x + $width - $rt), self::n($top))
            . $corner($x + $width, $top, $x + $width, $top + $rt, $roundTop)
            . sprintf('L%s,%s', self::n($x + $width), self::n($bottom - $rb))
            . $corner($x + $width, $bottom, $x + $width - $rb, $bottom, $roundBottom)
            . sprintf('L%s,%s', self::n($x + $rb), self::n($bottom))
            . $corner($x, $bottom, $x, $bottom - $rb, $roundBottom)
            . 'Z';
    }

    private function standaloneStyle(): string
    {
        $rules = '';
        foreach ($this->options->paletteDark as $i => $hex) {
            $rules .= sprintf('.wp-chart-series-%d{fill:%s;stroke:%s}.wp-chart-line.wp-chart-series-%d{fill:none}', $i + 1, $hex, $hex, $i + 1);
        }
        $d = self::INK_DARK;
        return sprintf(
            '<style>@media (prefers-color-scheme: dark){%s.wp-chart-grid{stroke:%s}.wp-chart-axis,.wp-chart-crosshair{stroke:%s}.wp-chart-tick{fill:%s}.wp-chart-label{fill:%s}.wp-chart-marker{stroke:%s}}</style>',
            $rules, $d['grid'], $d['axis'], $d['muted'], $d['text'], $d['surface']
        );
    }

    private function color(int $seriesIndex): string
    {
        return $this->options->palette[$seriesIndex % count($this->options->palette)];
    }

    private function fmt(float $value, int $decimals, string $unit = ''): string
    {
        $format = $this->options->format;
        $text = $format !== null ? $format($value, $decimals) : number_format($value, $decimals);
        if ($unit === '') {
            return $text;
        }
        return $unit === '%' ? $text . '%' : $text . ' ' . $unit;
    }

    /** @param list<float|null> $values */
    private static function lastValue(array $values): ?float
    {
        for ($i = count($values) - 1; $i >= 0; $i--) {
            if ($values[$i] !== null) {
                return $values[$i];
            }
        }
        return null;
    }

    private static function id(Chart $chart): string
    {
        return 'wpc' . substr(md5(serialize([$chart->type(), $chart->getTitle(), $chart->getLabels(), $chart->getSeries()])), 0, 8);
    }

    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }

    private static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
