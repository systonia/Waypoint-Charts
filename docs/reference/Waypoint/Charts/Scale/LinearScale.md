---
title: "LinearScale"
description: "A value axis with \"nice\" bounds and ticks stepping in 1, 2, 5 × 10^n."
order: 100
generated: true
---

`Waypoint\Charts\Scale\LinearScale` · final class · namespace [`Waypoint\Charts\Scale`](index.md)

```php
final class LinearScale
```

A value axis with "nice" bounds and ticks stepping in 1, 2, 5 × 10^n.

## Properties

### $min

```php
public readonly float $min;
```

Lowest tick, the bottom of the axis.

### $max

```php
public readonly float $max;
```

Highest tick, the top of the axis.

### $step

```php
public readonly float $step;
```

Distance between two ticks.

## Methods

### nice()

```php
public static function nice(float $lo, float $hi, int $ticks = 5, bool $includeZero = true): \Waypoint\Charts\Scale\LinearScale
```

Bounds around [$lo, $hi] on clean tick values, about $ticks intervals; $includeZero anchors the baseline.

**Parameters**

- `$lo` `float`
- `$hi` `float`
- `$ticks` `int`
- `$includeZero` `bool`

**Returns** [`LinearScale`](LinearScale.md)

### ticks()

```php
public function ticks(): array
```

**Returns** `list<float>` — Every tick from min to max inclusive.

### position()

```php
public function position(float $value, float $from, float $to): float
```

Pixel position of $value between $from (min) and $to (max).

**Parameters**

- `$value` `float`
- `$from` `float`
- `$to` `float`

**Returns** `float`

### decimals()

```php
public function decimals(): int
```

Decimals needed to print the ticks without rounding away the step.

**Returns** `int`
