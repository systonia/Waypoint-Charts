---
title: "Chart"
description: "A bar or line chart: labels, up to eight series and how to show them, built fluently."
order: 100
generated: true
---

`Waypoint\Charts\Chart` · final class · namespace [`Waypoint\Charts`](index.md)

```php
final class Chart
```

A bar or line chart: labels, up to eight series and how to show them, built fluently.

```php
$chart = Chart::bar()
    ->title('Sold this week')
    ->labels(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
    ->series('Croissants', [84, 76, 91, 88, 102, 140])
    ->series('Pretzels', [60, 58, 64, 61, 75, 96]);
```

Return it from a route to get an image/svg+xml response, or pass it to
`$this->chart($chart)` inside a view. SvgRenderer draws it.

## Constants

### BAR

```php
public const BAR = 'bar';
```

Type of a bar chart, for fromRows() and type().

### LINE

```php
public const LINE = 'line';
```

Type of a line chart, for fromRows() and type().

## Methods

### bar()

```php
public static function bar(): \Waypoint\Charts\Chart
```

A new bar chart: one bar per series at every label, grouped side by side or stacked().

**Returns** [`Chart`](Chart.md)

### line()

```php
public static function line(): \Waypoint\Charts\Chart
```

A new line chart: one line per series through its values, with markers by default.

**Returns** [`Chart`](Chart.md)

### fromRows()

```php
public static function fromRows(
    iterable $rows,
    string $x,
    string $y,
    ?string $series = null,
    string $type = self::BAR,
): \Waypoint\Charts\Chart
```

A chart from query rows: labels in order of first appearance, one series per distinct
value of the $series column.

Values of the same label and series add up; a label a series has no row for stays a gap
(null). A value that is not numeric counts as 0.

**Parameters**

- `$rows` `iterable<array<string, mixed>|object>`: Arrays or objects with the named keys or properties.
- `$x` `string`: Column with the label (the x position).
- `$y` `string`: Column with the value.
- `$series` `string|null`: Column that splits rows into series; null for one series with an empty name.
- `$type` `string`: Chart::BAR or Chart::LINE.

**Returns** [`Chart`](Chart.md)

### title()

```php
public function title(string $title): \Waypoint\Charts\Chart
```

Caption above the chart, the SVG's <title> and the table's <caption>.

**Parameters**

- `$title` `string`

**Returns** [`Chart`](Chart.md)

### description()

```php
public function description(string $description): \Waypoint\Charts\Chart
```

A sentence for screen readers describing what the chart shows; generated from the data when omitted.

**Parameters**

- `$description` `string`

**Returns** [`Chart`](Chart.md)

### labels()

```php
public function labels(array $labels): \Waypoint\Charts\Chart
```

**Parameters**

- `$labels` `list<string>`: One per x position, shared by every series.

**Returns** [`Chart`](Chart.md)

### series()

```php
public function series(string $name, array $values): \Waypoint\Charts\Chart
```

**Parameters**

- `$name` `string`
- `$values` `list<float|int|null>`: One per label; null leaves a gap. At most eight series.

**Returns** [`Chart`](Chart.md)

### unit()

```php
public function unit(string $unit, int $decimals = 0): \Waypoint\Charts\Chart
```

Unit appended to values in labels, tooltips and the table ('€', '%'), and how many decimals to show.

**Parameters**

- `$unit` `string`
- `$decimals` `int`

**Returns** [`Chart`](Chart.md)

### stacked()

```php
public function stacked(bool $stacked = true): \Waypoint\Charts\Chart
```

Bars only: stack the series instead of grouping them side by side.

**Parameters**

- `$stacked` `bool`

**Returns** [`Chart`](Chart.md)

### markers()

```php
public function markers(bool $markers = true): \Waypoint\Charts\Chart
```

Lines only: draw a marker at every point.

**Parameters**

- `$markers` `bool`

**Returns** [`Chart`](Chart.md)

### withTable()

```php
public function withTable(bool $table = true): \Waypoint\Charts\Chart
```

Show the data table under this chart; false hides it even when ChartsOptions::$table is on.

**Parameters**

- `$table` `bool`

**Returns** [`Chart`](Chart.md)

### size()

```php
public function size(int $width, int $height): \Waypoint\Charts\Chart
```

Drawing size in CSS pixels for this chart, at least 120 × 80; ChartsOptions' size otherwise.

**Parameters**

- `$width` `int`
- `$height` `int`

**Returns** [`Chart`](Chart.md)

### type()

```php
public function type(): string
```

Chart::BAR or Chart::LINE.

**Returns** `string`

### getTitle()

```php
public function getTitle(): ?string
```

The title, or null when none was set.

**Returns** `?string`

### getDescription()

```php
public function getDescription(): ?string
```

The screen-reader sentence, or null when the renderer generates one.

**Returns** `?string`

### getLabels()

```php
public function getLabels(): array
```

The labels, one per x position.

**Returns** `list<string>`

### getSeries()

```php
public function getSeries(): array
```

The series in the order they were added, values as floats and gaps as null.

**Returns** `list<array{name: string, values: list<float|null>}>`

### getUnit()

```php
public function getUnit(): string
```

The unit from unit(), '' when none.

**Returns** `string`

### getDecimals()

```php
public function getDecimals(): int
```

Decimals values are shown with.

**Returns** `int`

### isStacked()

```php
public function isStacked(): bool
```

True for a bar chart with stacked(); a line chart never stacks.

**Returns** `bool`

### hasMarkers()

```php
public function hasMarkers(): bool
```

Whether a line chart draws a marker at every point.

**Returns** `bool`

### hasTable()

```php
public function hasTable(): bool
```

Whether this chart asks for its data table (ChartsOptions::$table can still turn it off).

**Returns** `bool`

### getWidth()

```php
public function getWidth(): ?int
```

Width from size(), or null for ChartsOptions::$width.

**Returns** `?int`

### getHeight()

```php
public function getHeight(): ?int
```

Height from size(), or null for ChartsOptions::$height.

**Returns** `?int`
