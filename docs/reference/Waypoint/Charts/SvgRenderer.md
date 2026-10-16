---
title: "SvgRenderer"
description: "Draws a Chart as SVG."
order: 100
generated: true
---

`Waypoint\Charts\SvgRenderer` · final class · namespace [`Waypoint\Charts`](index.md)

```php
final class SvgRenderer
```

Draws a Chart as SVG. render() wraps it in a <figure> with caption, legend
and the data table (the accessible twin of the picture); renderStandalone()
returns a bare <svg> for image/svg+xml responses and <img> tags.

Marks carry their colors as presentation attributes (so the SVG is readable
without the stylesheet) and the plugin's CSS overrides them for dark mode.

## Methods

### __construct()

```php
public function __construct(private \Waypoint\Charts\ChartsOptions $options)
```

**Parameters**

- `$options` [`ChartsOptions`](ChartsOptions.md)

### render()

```php
public function render(\Waypoint\Charts\Chart $chart): string
```

The chart for an HTML page: a <figure> with caption, the SVG, a legend for two or more
series and the data table in a <details>. Every text is escaped.

**Parameters**

- `$chart` [`Chart`](Chart.md)

**Returns** `string`

### renderStandalone()

```php
public function renderStandalone(\Waypoint\Charts\Chart $chart): string
```

A bare <svg> document with its own dark-mode styles, for image/svg+xml responses, <img>
tags and files. It has no caption, legend or table.

**Parameters**

- `$chart` [`Chart`](Chart.md)

**Returns** `string`
