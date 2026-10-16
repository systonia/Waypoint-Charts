---
title: Pages, images and the script
description: The chart view helper, charts as image/svg+xml, the tooltip script, accessibility and rendering outside a view.
order: 20
---

A [`Chart`](../reference/Waypoint/Charts/Chart.md) becomes markup in one of two forms. In a page it is a `<figure>` with everything around the picture; as an image it is a bare SVG document. [`ChartsPlugin`](../reference/Waypoint/Charts/ChartsPlugin.md) connects both to Waypoint, and [`SvgRenderer`](../reference/Waypoint/Charts/SvgRenderer.md) does the drawing.

## In a view

The plugin adds the view helper `chart`. A controller passes the chart in the model, the view prints it:

```php
#[Get('/dashboard')]
public function dashboard(): View
{
    return new View('Dashboard', [
        'signups' => Chart::line()
            ->title('Signups')
            ->labels(['Mon', 'Tue', 'Wed', 'Thu', 'Fri'])
            ->series('Signups', [12, 18, 9, 22, 30]),
    ]);
}
```

```php
<section class="card">
    <?= $this->chart($model['signups']) ?>
</section>
```

The helper returns HTML, so it is printed as it is. It escapes every text that came from the chart (title, labels, series names, description) itself. The output is:

```html
<figure class="wp-chart wp-chart-line" wp-chart data-series="1">
  <figcaption class="wp-chart-title">Signups</figcaption>
  <div class="wp-chart-plot"><svg …>…</svg></div>
  <details class="wp-chart-details">
    <summary>Show data as table</summary>
    <table class="wp-chart-table">…</table>
  </details>
</figure>
```

- The `<figcaption>` is there when the chart has a title.
- A legend (`<ul class="wp-chart-legend">`) follows the plot when the chart has two or more series; one series needs none, the title names it.
- The data table is closed by default and lists every label and value, formatted as in the chart. `withTable(false)` on the chart or [`ChartsOptions::$table`](configuration.md#the-data-table) turns it off.

## The stylesheet and the script

The plugin ships `charts.css` and `charts.js`. Waypoint compiles them with content hashes into its asset path (`/assets/charts.charts.<hash>.css` with the default `assetsPath`), and `$this->pluginAssetTags()` prints the `<link>` and `<script>` tags. The script uses the directives of `waypoint.js`, so the layout prints `waypointJsTag()` first:

```php
<body>
<?= $content ?>
<?= $this->waypointJsTag() ?>
<?= $this->pluginAssetTags() ?>
</body>
```

The stylesheet scales the SVG to the width of its container, sets the colors through CSS custom properties (see [Colors](configuration.md#colors)), and styles legend, table and tooltip. Without it the SVG still draws correctly at its own size, because every mark carries its color as an attribute.

The script adds, for every chart on the page:

- **a tooltip** when the pointer is over an x position or it has keyboard focus: the label and the value of every series, each with its color key;
- **a crosshair** on line charts at that position, and a highlight of the marks there;
- **arrow keys**: every x position is a focus stop (`Tab` reaches the first), `←` and `→` move between them.

The tooltip reads its numbers from the chart's data table, as text, so picture, table and tooltip always agree. A chart with `withTable(false)` therefore gets no tooltip from the script. When the script loads, it removes the native `<title>` tooltips of the hit areas, which are what a browser without the script shows.

## As an image

A route that returns a `Chart` answers with the chart as an SVG document:

```php
#[Get('/reports/revenue.svg')]
public function revenueSvg(): Chart
{
    return Chart::bar()
        ->title('Revenue 2026')
        ->labels(['Jan', 'Feb', 'Mar'])
        ->series('Domestic', [120, 140, 165])
        ->unit('€');
}
```

```
HTTP/1.1 200 OK
Content-Type: image/svg+xml; charset=utf-8

<svg xmlns="http://www.w3.org/2000/svg" class="wp-chart-svg" viewBox="0 0 640 320" width="640" height="320" role="img" …>
```

Use it wherever an image goes: `<img src="/reports/revenue.svg" alt="…">`, a PDF renderer, a download. The standalone SVG has the title and description but no caption, legend or table, and it needs no stylesheet: its own `<style>` switches to the dark palette under `prefers-color-scheme: dark`.

Give the `<img>` an `alt` text; an image's inner `<title>` is not what screen readers announce for it.

The route can take parameters like any other, for example a year in the path:

```php
#[Get('/reports/{year}/revenue.svg')]
public function revenueForYear(int $year): Chart
{
    return Chart::fromRows($this->orders->revenueByMonth($year), x: 'month', y: 'revenue')
        ->title("Revenue $year")
        ->unit('€');
}
```

## Outside a view

The plugin registers its [`SvgRenderer`](../reference/Waypoint/Charts/SvgRenderer.md) in the container, configured with the app's `ChartsOptions`. A service gets it with `#[Inject]`, app code with `$app->get(SvgRenderer::class)`:

```php
use Waypoint\Attributes\Inject;
use Waypoint\Charts\{Chart, SvgRenderer};

final class WeeklyReportMail
{
    #[Inject]
    private SvgRenderer $charts;

    public function html(Chart $chart): string
    {
        return '<p>Your weekly report:</p>' . $this->charts->render($chart->withTable(false));
    }
}
```

| Method | Returns |
|---|---|
| `render(Chart $chart)` | the `<figure>` with caption, SVG, legend and table, as the view helper does |
| `renderStandalone(Chart $chart)` | the bare `<svg>` document, as a route returning a chart does |

`renderStandalone()` also writes files:

```php
file_put_contents(__DIR__ . '/var/revenue.svg', $app->get(SvgRenderer::class)->renderStandalone($chart));
```

## Accessibility

Every chart is readable without seeing it:

- The SVG has `role="img"`, a `<title>` (the chart's title, or `Chart`) and a `<desc>` with the [description](charts.md#the-description).
- Every x position is a focusable area whose `aria-label` reads the label and all values, such as `Feb: Domestic 140 €, Export 55 €`.
- The data table under the chart has a caption, column headers for the series and row headers for the labels.
- Text is never drawn in a series color; colors identify series in marks, legend and tooltip only.

The SVG's element ids come from the chart's type, title, labels and series. Two charts with exactly the same data on one page get the same ids; give them different titles.

See also: [`SvgRenderer`](../reference/Waypoint/Charts/SvgRenderer.md), [Configuration and colors](configuration.md).
