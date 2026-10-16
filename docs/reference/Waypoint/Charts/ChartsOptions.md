---
title: "ChartsOptions"
description: "Settings for every chart the plugin draws."
order: 100
generated: true
---

`Waypoint\Charts\ChartsOptions` · class · namespace [`Waypoint\Charts`](index.md)

```php
class ChartsOptions
```

Settings for every chart the plugin draws.

```php
$app->configure(function (ChartsOptions $charts) {
    $charts->height = 280;
    $charts->format = fn(float $value, int $decimals): string => number_format($value, $decimals, ',', '.');
});
```

## Properties

### $width

```php
public int $width = 640;
```

Default drawing width in CSS pixels; the SVG scales with its container via viewBox.

### $height

```php
public int $height = 320;
```

Default drawing height in CSS pixels.

### $maxBarWidth

```php
public int $maxBarWidth = 24;
```

Widest a bar may get; the rest of its slot stays air.

### $format

```php
public ?\Closure $format = null;
```

**Type** `(`[`Closure`](https://www.php.net/manual/en/class.closure.php)`(float $value, int $decimals): string)|null`

Formats every value: axis ticks, labels, tooltips and the table. Default: number_format with '.' and ','.
The unit is appended after it.

### $table

```php
public bool $table = true;
```

Render the data table (<details>) under every chart. Keep it: it is the accessible twin of the picture.

### $tableLabel

```php
public string $tableLabel = 'Show data as table';
```

Text of the <summary> that opens the data table.

### $palette

```php
public array $palette = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
```

**Type** `list<string>`

Eight categorical colors for the light surface, in the fixed slot order (validated colorblind-safe).

Series colors written into the SVG, in slot order; the plugin's stylesheet sets its own through CSS properties.

### $paletteDark

```php
public array $paletteDark = ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'];
```

**Type** `list<string>`

The same eight hues stepped for the dark surface.

Series colors for dark mode inside a standalone SVG (a Chart returned from a route).
