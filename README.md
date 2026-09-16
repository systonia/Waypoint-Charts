# waypoint-charts

Bar and line charts for [Waypoint](https://github.com/Systonia/Waypoint), rendered as SVG on the
server. They work without JavaScript, print, go into PDFs and e-mails, and ship their data as a real
table. `waypoint.js` adds tooltips, a crosshair and keyboard navigation on top.

## Setup

```php
$app->plugin(new Waypoint\Charts\ChartsPlugin());   // before attach()
```

The layout renders the stylesheet and script with `$this->pluginAssetTags()`.

## Usage

```php
use Waypoint\Charts\Chart;

$chart = Chart::bar()
    ->title('Revenue 2026')
    ->labels(['Jan', 'Feb', 'Mar'])
    ->series('Domestic', [120, 140, 165])
    ->series('Export', [40, 55, 70])
    ->unit('€');
```

- In a view: `<?= $this->chart($chart) ?>` renders a `<figure>` with caption, the SVG, a legend (two
  or more series) and the data table in a `<details>`.
- From a route: `return $chart;` answers with `image/svg+xml` — use it as `<img src="/reports/revenue.svg">`
  or inside a PDF; the SVG carries its own dark-mode styles.
- From a query: `Chart::fromRows($rows, x: 'month', y: 'revenue', series: 'region')` pivots rows into
  labels and series (duplicates add up).

Builder options: `line()` or `bar()`, `stacked()` (bars), `markers(false)` (lines), `unit('€', decimals: 2)`,
`description('...')` for the screen-reader sentence (generated from the data when omitted),
`size(width, height)`, `withTable(false)`. At most eight series — fold the rest into "Other" or draw a
second chart; never two value axes on one chart.

## What you get by design

- Thin marks: bars at most 24px wide with a rounded data end and a square baseline, 2px lines, 8px markers
  with a surface ring, 2px surface gaps in stacks. Gridlines are hairlines.
- Colors are assigned to series in a fixed, validated colorblind-safe order and follow the series, never
  its rank. Text never wears a series color.
- A legend for two or more series; the maximum of a single-series bar chart and the end value of each
  line are labelled directly. Everything else is in the tooltip and the table.
- `role="img"` with `<title>` and a generated `<desc>`; every x position is a focusable hit area with the
  values in its `aria-label`; arrow keys walk the positions once the script is loaded.
- Light and dark mode from the same eight hues, stepped for each surface.

## Options

| Setting | Default | Description |
|---|---|---|
| `width`, `height` | `640`, `320` | Drawing size; the SVG scales with its container. |
| `maxBarWidth` | `24` | Bars never get thicker than this. |
| `format` | `null` | `Closure(float $value, int $decimals): string`; default `number_format` with `.` and `,`. |
| `table` | `true` | Render the data table under every chart. |
| `tableLabel` | `'Show data as table'` | Text of the `<summary>`. |
| `palette`, `paletteDark` | validated defaults | Eight categorical colors per surface, in slot order. Validate a replacement before shipping it. |

## Development

```bash
composer install && composer test && composer stan
```
