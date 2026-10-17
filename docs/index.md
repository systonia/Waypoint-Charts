---
title: Waypoint Charts
description: Bar and line charts for Waypoint, drawn as SVG on the server, with a legend, a data table and tooltips.
order: 1
---

Waypoint Charts draws bar and line charts on the server and sends them as SVG. A chart is a [`Chart`](reference/Waypoint/Charts/Chart.md) object built in PHP: labels along the x axis, up to eight series of values, a unit. The plugin turns it into markup in two places:

- in a view, `{{ $this->chart($chart) }}` renders a `<figure>` with the caption, the SVG, a legend and the data table;
- returned from a route, the chart is the response, as `image/svg+xml`, for an `<img>` tag, a PDF or a file.

The picture is complete without JavaScript: it prints, works in e-mails and in documents, and carries its numbers as a real HTML table. The plugin's script adds a tooltip, a crosshair on line charts and arrow-key navigation on top. Colors follow a fixed colorblind-safe order, and every chart has a light and a dark version.

## Install

```bash
composer require systonia/waypoint-charts
```

The package needs PHP 8.5 and `systonia/waypoint` 0.7.7 or later in the 0.x line. Register the plugin before `attach()`:

```php
$app->plugin(new Waypoint\Charts\ChartsPlugin());
```

The layout loads the plugin's stylesheet and script with `$this->pluginAssetTags()`, after `$this->waypointJsTag()`, because the script builds on `waypoint.js`.

## A first chart

The examples in these docs come from Sonnenkorn, a made-up bakery and the [Waypoint example app](https://github.com/systonia/Waypoint-Bakery). Its back office shows the owner what the shop sold. The smallest version of that page is one controller, one chart and two views.

`index.php`:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Waypoint\Attributes\{Controller, Get};
use Waypoint\Charts\{Chart, ChartsPlugin};
use Waypoint\Http\View;
use Waypoint\Options\RendererOptions;
use Waypoint\Waypoint;

#[Controller('/sales')]
final class SalesController
{
    #[Get('')]
    public function page(): View
    {
        return new View('Sales', $this->week());
    }

    #[Get('/week.svg')]
    public function svg(): Chart
    {
        return $this->week();
    }

    private function week(): Chart
    {
        return Chart::bar()
            ->title('Sold this week')
            ->labels(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
            ->series('Croissants', [84, 76, 91, 88, 102, 140])
            ->series('Pretzels', [60, 58, 64, 61, 75, 96]);
    }
}

$app = Waypoint::create();
$app->plugin(new ChartsPlugin());
$app->configure(function (RendererOptions $renderer) {
    $renderer->directory = __DIR__ . '/views';
    $renderer->layout = '_Layout.php';
});
$app->attach([SalesController::class]);
$app->serve();
```

`views/_Layout.php`:

```php
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sonnenkorn</title>
</head>
<body>
<main>{{ $content }}</main>
{{ $this->waypointJsTag() }}
{{ $this->pluginAssetTags() }}
</body>
</html>
```

`views/Sales.php`:

```php
@model (Waypoint\Charts\Chart)

<h1>
    Sales
</h1>
{{ $this->chart($model) }}
<p>
    <img src="/sales/week.svg" alt="Croissants and pretzels sold this week" width="640" height="320">
</p>
```

Run it with `tenma index.php` (or `php -S localhost:8080 index.php`). `/sales` shows the chart twice: once as the interactive figure with legend, table and tooltips, once as a plain image loaded from `/sales/week.svg`, which answers with `Content-Type: image/svg+xml; charset=utf-8`.

The bakery itself builds its charts from the sales in Postgres with [`Chart::fromRows()`](guides/charts.md#charts-from-query-rows) and puts them on the page through a typed model; [Pages, images and the script](guides/rendering.md#in-a-view) shows that page.

## Overview

| Piece | What it is |
|---|---|
| [`Chart`](reference/Waypoint/Charts/Chart.md) | What to draw: `Chart::bar()` or `Chart::line()`, labels, series, unit, title, size; or `Chart::fromRows()` from query rows |
| [`ChartsPlugin`](reference/Waypoint/Charts/ChartsPlugin.md) | Registers the view helper `chart`, renders a `Chart` returned from a route, ships `charts.css` and `charts.js` |
| [`ChartsOptions`](reference/Waypoint/Charts/ChartsOptions.md) | Size, bar width, number format, the data table and the palettes, for every chart |
| [`SvgRenderer`](reference/Waypoint/Charts/SvgRenderer.md) | The service that draws; `render()` for pages, `renderStandalone()` for a bare SVG |

## How these docs are organised

- **[Guides](guides/index.md)** explain one part each, with examples that run: building charts, putting them on pages and into images, configuration and colors, testing.
- **[Reference](reference/index.md)** has one page per class, generated from the code with `tenma docs`.

Start with [Building charts](guides/charts.md), then [Pages, images and the script](guides/rendering.md).
