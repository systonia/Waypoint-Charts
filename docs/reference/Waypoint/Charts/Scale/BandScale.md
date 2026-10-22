---
title: "BandScale"
description: "Evenly spaced slots for categories along an axis, each with inner padding."
order: 100
generated: true
---

`Waypoint\Charts\Scale\BandScale` · final class · namespace [`Waypoint\Charts\Scale`](index.md)

```php
final class BandScale
```

Evenly spaced slots for categories along an axis, each with inner padding.

## Methods

### __construct()

```php
public function __construct(
    int $count,
    private float $start,
    float $width,
    private float $padding = 0.2,
)
```

**Parameters**

- `$count` `int`: Number of slots.
- `$start` `float`: Position of the first slot's left edge.
- `$width` `float`: Width of all slots together.
- `$padding` `float`: Share of a slot (0 to 1) left empty, split between both sides.

### band()

```php
public function band(): float
```

Width of one slot.

**Returns** `float`

### inner()

```php
public function inner(): float
```

Usable width inside a slot (the padding stays air).

**Returns** `float`

### start()

```php
public function start(int $index): float
```

Left edge of slot $index.

**Parameters**

- `$index` `int`

**Returns** `float`

### innerStart()

```php
public function innerStart(int $index): float
```

Left edge of the usable width inside slot $index.

**Parameters**

- `$index` `int`

**Returns** `float`

### center()

```php
public function center(int $index): float
```

Center of slot $index.

**Parameters**

- `$index` `int`

**Returns** `float`
