---
title: Building charts
description: Bar and line charts, series, gaps, stacking, units, sizes and charts from query rows.
order: 10
---

A [`Chart`](../reference/Waypoint/Charts/Chart.md) describes what to draw. It starts with `Chart::bar()` or `Chart::line()`, and every other method sets one thing and returns the chart, so a chart is built in one expression:

```php
use Waypoint\Charts\Chart;

$chart = Chart::line()
    ->title('Visitors per weekday')
    ->labels(['Mon', 'Tue', 'Wed', 'Thu', 'Fri'])
    ->series('This week', [420, 515, 480, 610, 590])
    ->series('Last week', [390, 460, 470, 520, 600]);
```

A chart holds no reference to the app. It can be built in a controller, a service or a test, and rendered later.

## Labels and series

`labels()` sets the x positions, one string per position; numbers are converted to strings. Every series shares them: `series($name, $values)` takes one value per label, in the same order.

| Method | |
|---|---|
| `labels(array $labels)` | one label per x position; replaces earlier labels |
| `series(string $name, array $values)` | adds a series; values are `int`, `float` or `null` |
| `title(string $title)` | caption above the chart, the SVG's `<title>`, the table's `<caption>` |
| `description(string $text)` | the sentence screen readers get; generated from the data when omitted |
| `unit(string $unit, int $decimals = 0)` | unit and decimals for every value shown |
| `stacked(bool $stacked = true)` | bars only: stack the series instead of placing them side by side |
| `markers(bool $markers = true)` | lines only: a marker at every point (on by default) |
| `withTable(bool $table = true)` | the data table under this chart (on by default) |
| `size(int $width, int $height)` | drawing size for this chart, at least 120 × 80 |

Series are drawn and colored in the order they are added: the first series always gets the first color, the second the second. A chart takes at most eight series; the ninth `series()` call throws an `InvalidArgumentException` with the message *A chart takes at most eight series; fold the rest into "Other" or draw a second chart.*

```php
$top = array_slice($revenueByRegion, 0, 7, true);
$other = array_slice($revenueByRegion, 7, null, true);

$chart = Chart::bar()->title('Revenue by region')->labels($months);
foreach ($top as $region => $values) {
    $chart->series($region, $values);
}
if ($other !== []) {
    $chart->series('Other', array_map(fn(float|int|null ...$column) => array_sum($column), ...array_values($other)));
}
```

A series may have an empty name. A chart with a single series named `''` shows no name in tooltips, the table head or the generated description.

## Bar charts

`Chart::bar()` draws one bar per series at every label, side by side, with a small gap between them. Bars are at most 24 pixels wide (`ChartsOptions::$maxBarWidth`); a chart with few labels keeps the rest of each slot empty instead of drawing wide blocks. The value end of a bar is rounded, the baseline end square.

```php
$chart = Chart::bar()
    ->title('Orders per channel')
    ->labels(['Q1', 'Q2', 'Q3', 'Q4'])
    ->series('Shop', [310, 290, 355, 480])
    ->series('Phone', [120, 95, 101, 140])
    ->series('Marketplace', [60, 88, 130, 175]);
```

With `stacked()`, the series of one label sit on top of each other, separated by a 2-pixel gap, and only the outermost bar of a stack is rounded:

```php
$chart = Chart::bar()
    ->title('Hours booked')
    ->labels(['Week 40', 'Week 41', 'Week 42'])
    ->series('Development', [62, 70, 55])
    ->series('Support', [18, 12, 20])
    ->series('Meetings', [9, 11, 8])
    ->unit('h')
    ->stacked();
```

Negative values go below the zero line. In a stacked chart, the positive values of a label stack upwards from zero and the negative ones downwards, each on its own.

A bar chart with exactly one series that is not stacked labels its highest bar with the value directly above it. Every other value is in the tooltip and the table.

## Line charts

`Chart::line()` draws one 2-pixel line per series through the centers of the label slots, with an 8-pixel marker at every point. `markers(false)` leaves only the lines, which suits long series:

```php
$days = array_map(fn(int $d): string => sprintf('%02d.10.', $d), range(1, 31));

$chart = Chart::line()
    ->title('Active sessions, October')
    ->labels($days)
    ->series('Sessions', $sessionsPerDay)   // 31 values
    ->markers(false);
```

With up to four series, the last value of each line is written at its end. When two end labels would be closer than 12 pixels, the later one is left out. With five or more series there are no end labels, only the legend.

Long label lists are thinned: when the labels would overlap, only every second, third, … label is written under the axis. The data table and the tooltips keep all of them.

`stacked()` has no effect on a line chart, `markers()` none on a bar chart.

## Gaps

`null` is a missing value, not a zero. A bar chart leaves the slot empty; a line chart lifts the pen and continues at the next value, so the line has a visible gap. The table, the tooltip and the screen-reader text show `–`.

```php
$chart = Chart::line()
    ->title('Temperature')
    ->labels(['06:00', '09:00', '12:00', '15:00', '18:00'])
    ->series('Sensor A', [11.5, 14.0, null, 19.5, 16.0])   // no reading at noon
    ->unit('°C', 1);
```

A series with fewer values than labels treats the missing ones as gaps too.

## Units and decimals

`unit($unit, $decimals)` applies to every value the chart shows: axis ticks, direct labels, tooltips, the table and the generated description. A `%` is attached directly (`12.5%`); every other unit follows after a space (`165 €`, `3.2 h`).

```php
$chart = Chart::bar()
    ->title('Conversion rate')
    ->labels(['Landing page A', 'Landing page B'])
    ->series('Rate', [3.25, 4.1])
    ->unit('%', 1);
```

Values are shown with `$decimals` decimals. Axis ticks use more when the step between them needs it: an axis from 0 to 1 in steps of 0.2 shows one decimal even when `$decimals` is 0. The number format itself (separators) is [`ChartsOptions::$format`](configuration.md#number-format).

## The value axis

The axis always includes zero, so bars start at a common baseline and a line's height can be compared with it. Its ends and ticks fall on round numbers (steps of 1, 2 or 5 times a power of ten, about five of them) and grow with the data: values from 120 to 165 give an axis from 0 to 200 in steps of 50.

Two series with different units or very different ranges do not belong on one value axis. Draw two charts.

## Size

Charts are 640 × 320 CSS pixels unless `ChartsOptions` says otherwise. `size()` overrides that for one chart:

```php
$sparkline = Chart::line()->labels($hours)->series('Requests', $requests)->markers(false)->size(240, 90)->withTable(false);
```

The size sets the SVG's coordinate system (`viewBox`) and its `width` and `height` attributes. Inside a page, the plugin's stylesheet makes the SVG as wide as its container and keeps its proportions. Sizes below 120 × 80 are raised to that.

## The description

Screen readers announce a chart by its title and a description. Without `description()`, the chart describes itself from its data:

> Bar chart with 3 categories from Jan to Mar; series: Domestic, Export. Highest value 165 € at Mar (Domestic).

Set it when the chart has a point to make that the numbers alone do not say:

```php
$chart->description('Export revenue grew in every month of the first quarter, from 40 € to 70 €.');
```

## Charts from query rows

`Chart::fromRows()` builds a chart from rows as a database returns them: `$x` names the label column, `$y` the value column, and `$series` the column that splits the rows into series.

```php
$rows = $db->query(
    'SELECT to_char(ordered_at, \'YYYY-MM\') AS month, region, sum(total) AS revenue
       FROM orders GROUP BY 1, 2 ORDER BY 1'
)->fetchAll(PDO::FETCH_ASSOC);

$chart = Chart::fromRows($rows, x: 'month', y: 'revenue', series: 'region')
    ->title('Revenue by region')
    ->unit('€');
```

With rows like these:

| month | region | revenue |
|---|---|---|
| 2026-01 | North | 120.50 |
| 2026-01 | South | 80 |
| 2026-02 | North | 140 |
| 2026-02 | North | 10 |
| 2026-03 | South | 95 |

the chart gets the labels `2026-01`, `2026-02`, `2026-03` (in the order they first appear) and two series: North with `120.5, 150, null` and South with `80, null, 95`.

- Rows of the same label and series add up (North in February: 140 + 10).
- A label a series has no row for is a gap (`null`), not a zero.
- Values arrive as strings from most database drivers; numeric strings are converted, anything not numeric counts as 0.
- Rows may be arrays or objects (`PDO::FETCH_OBJ`, DTOs) with the named keys or properties.
- Without `$series`, all rows form one series with an empty name.
- `type: Chart::LINE` makes a line chart; the default is `Chart::BAR`.

The chart orders labels as the rows come, so sort in the query. More than eight distinct values in the series column throw, as `series()` does.

See also: [`Chart`](../reference/Waypoint/Charts/Chart.md), [Pages, images and the script](rendering.md).
