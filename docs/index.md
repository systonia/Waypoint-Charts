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

The package needs PHP 8.5 and `systonia/waypoint` 0.6.4 or later in the 0.x line. Register the plugin before `attach()`:

```php
$app->plugin(new Waypoint\Charts\ChartsPlugin());
```

The layout loads the plugin's stylesheet and script with `$this->pluginAssetTags()`, after `$this->waypointJsTag()`, because the script builds on `waypoint.js`.

## A first chart

`index.php`:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Waypoint\Attributes\{Controller, Get};
use Waypoint\Charts\{Chart, ChartsPlugin};
use Waypoint\Http\View;
use Waypoint\Options\RendererOptions;
use Waypoint\Waypoint;

#[Controller('/reports')]
final class ReportsController
{
    #[Get('')]
    public function page(): View
    {
        return new View('Revenue', ['chart' => $this->revenue()]);
    }

    #[Get('/revenue.svg')]
    public function svg(): Chart
    {
        return $this->revenue();
    }

    private function revenue(): Chart
    {
        return Chart::bar()
            ->title('Revenue 2026')
            ->labels(['Jan', 'Feb', 'Mar'])
            ->series('Domestic', [120, 140, 165])
            ->series('Export', [40, 55, 70])
            ->unit('€');
    }
}

$app = Waypoint::create();
$app->plugin(new ChartsPlugin());
$app->configure(function (RendererOptions $renderer) {
    $renderer->directory = __DIR__ . '/views';
    $renderer->layout = '_Layout.php';
});
$app->attach([ReportsController::class]);
$app->serve();
```

`views/_Layout.php`:

```php
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reports</title>
</head>
<body>
<main>{{ $content }}</main>
{{ $this->waypointJsTag() }}
{{ $this->pluginAssetTags() }}
</body>
</html>
```

`views/Revenue.php`:

```php
<h1>Revenue</h1>
{{ $this->chart($model['chart']) }}
<p><img src="/reports/revenue.svg" alt="Revenue 2026 by month" width="640" height="320"></p>
```

Run it with `tenma index.php` (or `php -S localhost:8080 index.php`). `/reports` shows the chart twice: once as the interactive figure with legend, table and tooltips, once as a plain image loaded from `/reports/revenue.svg`, which answers with `Content-Type: image/svg+xml; charset=utf-8`.

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
