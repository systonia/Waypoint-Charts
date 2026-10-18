---
title: Building charts
description: Bar and line charts, series, gaps, stacking, units, sizes and charts from query rows.
order: 10
---

A [`Chart`](../reference/Waypoint/Charts/Chart.md) describes what to draw. It starts with `Chart::bar()` or `Chart::line()`, and every other method sets one thing and returns the chart, so a chart is built in one expression:

```php
use Waypoint\Charts\Chart;

$chart = Chart::line()
    ->title('Croissants sold per weekday')
    ->labels(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
    ->series('This week', [84, 76, 91, 88, 102, 140])
    ->series('Last week', [79, 81, 85, 90, 97, 131]);
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

The bakery sells more than eight products. A chart of each product's sales per weekday keeps the seven best and adds up the rest (`$soldByProduct` maps a product name to its six values, best first):

```php
$top = array_slice($soldByProduct, 0, 7, true);
$other = array_slice($soldByProduct, 7, null, true);

$chart = Chart::bar()->title('Sold per weekday')->labels(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']);
foreach ($top as $product => $values) {
    $chart->series($product, $values);
}
if ($other !== []) {
    $chart->series('Other', array_map(fn (float|int|null ...$column) => array_sum($column), ...array_values($other)));
}
```

A series may have an empty name. A chart with a single series named `''` shows no name in tooltips, the table head or the generated description.

## Bar charts

`Chart::bar()` draws one bar per series at every label, side by side, with a small gap between them. Bars are at most 24 pixels wide (`ChartsOptions::$maxBarWidth`); a chart with few labels keeps the rest of each slot empty instead of drawing wide blocks. The value end of a bar is rounded, the baseline end square.

```php
$chart = Chart::bar()
    ->title('Sold per weekday')
    ->labels(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
    ->series('Bread', [42, 38, 45, 40, 58, 77])
    ->series('Rolls', [180, 165, 172, 170, 210, 320])
    ->series('Pastries', [96, 88, 104, 99, 121, 168]);
```

With `stacked()`, the series of one label sit on top of each other, separated by a 2-pixel gap, and only the outermost bar of a stack is rounded:

```php
$chart = Chart::bar()
    ->title('Hours in the bakery')
    ->labels(['Week 40', 'Week 41', 'Week 42'])
    ->series('Baking', [62, 70, 55])
    ->series('Shop counter', [48, 52, 50])
    ->series('Cleaning', [9, 11, 8])
    ->unit('h')
    ->stacked();
```

Negative values go below the zero line. In a stacked chart, the positive values of a label stack upwards from zero and the negative ones downwards, each on its own.

A bar chart with exactly one series that is not stacked labels its highest bar with the value directly above it. Every other value is in the tooltip and the table.

## Line charts

`Chart::line()` draws one 2-pixel line per series through the centers of the label slots, with an 8-pixel marker at every point. `markers(false)` leaves only the lines, which suits long series:

```php
$days = array_map(fn (int $d): string => sprintf('%02d.10.', $d), range(1, 31));

$chart = Chart::line()
    ->title('Orders per day, October')
    ->labels($days)
    ->series('Orders', $ordersPerDay)   // 31 values
    ->markers(false);
```

With up to four series, the last value of each line is written at its end. When two end labels would be closer than 12 pixels, the later one is left out. With five or more series there are no end labels, only the legend.

Long label lists are thinned: when the labels would overlap, only every second, third, … label is written under the axis. The data table and the tooltips keep all of them.

`stacked()` has no effect on a line chart, `markers()` none on a bar chart.

## Gaps

`null` is a missing value, not a zero. A bar chart leaves the slot empty; a line chart lifts the pen and continues at the next value, so the line has a visible gap. The table, the tooltip and the screen-reader text show `–`.

```php
$chart = Chart::line()
    ->title('Revenue, first week of October')
    ->labels(['1 Oct', '2 Oct', '3 Oct', '4 Oct', '5 Oct'])
    ->series('Revenue', [1180.5, 1240.0, null, 1515.0, 1302.5])   // 3 October: holiday, the shop is closed
    ->unit('€', 2);
```

A series with fewer values than labels treats the missing ones as gaps too.

## Units and decimals

`unit($unit, $decimals)` applies to every value the chart shows: axis ticks, direct labels, tooltips, the table and the generated description. A `%` is attached directly (`32.5%`); every other unit follows after a space (`140 €`, `3.2 h`).

```php
$chart = Chart::bar()
    ->title('Sold out by noon')
    ->labels(['Croissant', 'Pretzel', 'Rye loaf'])
    ->series('Days', [32.5, 18.0, 7.5])
    ->unit('%', 1);
```

Values are shown with `$decimals` decimals. Axis ticks use more when the step between them needs it: an axis from 0 to 1 in steps of 0.2 shows one decimal even when `$decimals` is 0. The number format itself (separators) is [`ChartsOptions::$format`](configuration.md#number-format).

## The value axis

The axis always includes zero, so bars start at a common baseline and a line's height can be compared with it. Its ends and ticks fall on round numbers (steps of 1, 2 or 5 times a power of ten, about five of them) and grow with the data: values from 76 to 140 give an axis from 0 to 150 in steps of 50.

Two series with different units or very different ranges do not belong on one value axis. Draw two charts.

## Size

Charts are 640 × 320 CSS pixels unless `ChartsOptions` says otherwise. `size()` overrides that for one chart: the bakery's chart of the products sold is `->size(640, 280)`. A small chart of today's orders per hour, next to the order board:

```php
$sparkline = Chart::line()->labels($hours)->series('Orders', $ordersPerHour)->markers(false)->size(240, 90)->withTable(false);
```

The size sets the SVG's coordinate system (`viewBox`) and its `width` and `height` attributes. Inside a page, the plugin's stylesheet makes the SVG as wide as its container and keeps its proportions. Sizes below 120 × 80 are raised to that.

## The description

Screen readers announce a chart by its title and a description. Without `description()`, the chart describes itself from its data:

> Bar chart with 6 categories from Mon to Sat; series: Croissants, Pretzels. Highest value 140 at Sat (Croissants).

Set it when the chart has a point to make that the numbers alone do not say:

```php
$chart->description('Saturday sells more croissants and pretzels than any other day, about half again as many as a weekday.');
```

## Charts from query rows

`Chart::fromRows()` builds a chart from rows as a database returns them: `$x` names the label column, `$y` the value column, and `$series` the column that splits the rows into series.

The bakery's sales page draws the revenue of the last four weeks. Its repository returns one `DailyRevenue` per day (from `src/Repositories/SalesReport.php`, shortened):

```php
/** @return list<DailyRevenue> one per day, also for days without sales */
public function revenueByDay(int $days, string $locale): array
{
    $rows = $this->db->query(<<<'SQL'
        SELECT to_char(d, $2) AS day, coalesce(sum(s.revenue_cents), 0) / 100.0 AS revenue
        FROM generate_series(current_date - ($1::int - 1), current_date, interval '1 day') AS d
        LEFT JOIN daily_sales s ON s.day = d::date
        GROUP BY d ORDER BY d
        SQL, [$days, $locale === 'de' ? 'DD.MM.' : 'DD Mon']);
    return array_map(fn (array $values) => DailyRevenue::of(new Row($values)), $rows);
}
```

`DailyRevenue` is a readonly class with the properties `$day` and `$revenue`, and `fromRows()` reads them by name (from `src/Controllers/BackofficeController.php`):

```php
$revenue = Chart::fromRows($this->sales->revenueByDay(28, $locale), x: 'day', y: 'revenue')
    ->title($this->translator->t('Revenue per day, last four weeks'))
    ->unit('€');
```

With `$series`, the rows become several series. The sales per day and product, as a query on the same table returns them:

```php
$rows = $this->db->query(<<<'SQL'
    SELECT to_char(s.day, 'DD Mon') AS day, p.name ->> 'en' AS product, s.quantity
    FROM daily_sales s JOIN products p ON p.id = s.product_id
    WHERE s.day > current_date - 3 AND p.slug IN ('croissant', 'pretzel')
    ORDER BY s.day
    SQL);

$chart = Chart::fromRows($rows, x: 'day', y: 'quantity', series: 'product')
    ->title('Croissants and pretzels');
```

With rows like these:

| day | product | quantity |
|---|---|---|
| 15 Oct | Croissant | 88 |
| 15 Oct | Pretzel | 61 |
| 16 Oct | Croissant | 90 |
| 16 Oct | Croissant | 12 |
| 17 Oct | Pretzel | 96 |

the chart gets the labels `15 Oct`, `16 Oct`, `17 Oct` (in the order they first appear) and two series: Croissant with `88, 102, null` and Pretzel with `61, null, 96`.

- Rows of the same label and series add up (croissants on 16 October: 90 + 12).
- A label a series has no row for is a gap (`null`), not a zero.
- Numeric strings, as some database drivers return them, are converted; anything not numeric counts as 0.
- Rows may be arrays or objects (DTOs, readonly models like `DailyRevenue`) with the named keys or properties.
- Without `$series`, all rows form one series with an empty name, as in the bakery's revenue chart.
- `type: Chart::LINE` makes a line chart; the default is `Chart::BAR`.

The chart orders labels as the rows come, so sort in the query. More than eight distinct values in the series column throw, as `series()` does.

See also: [`Chart`](../reference/Waypoint/Charts/Chart.md), [Pages, images and the script](rendering.md).
