---
title: Testing
description: Check charts in feature tests through the app, and chart building and drawing in unit tests.
order: 40
---

Charts are plain objects and plain strings, so they test well at two levels: through the app with Waypoint's testing kit, and directly without an app.

## Through the app

`Waypoint\Testing\TestCase` sends requests through the app without a server. Register the plugin in `setUp()` as the app does:

```php
use Waypoint\Charts\ChartsPlugin;
use Waypoint\Options\RendererOptions;
use Waypoint\Testing\TestCase;

final class SalesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = $this->app();
        $app->plugin(new ChartsPlugin());
        $app->configure(function (RendererOptions $renderer) {
            $renderer->directory = __DIR__ . '/../views';
            $renderer->layout = '_Layout.php';
        });
        $app->attach([SalesController::class]);
    }

    public function testTheSalesPageShowsTheChartWithItsTable(): void
    {
        $this->get('/sales')
            ->assertOk()
            ->assertSee('<figure class="wp-chart wp-chart-bar"')
            ->assertSee('<figcaption class="wp-chart-title">Sold this week</figcaption>')
            ->assertSee('<ul class="wp-chart-legend">')
            ->assertSee('<th scope="row">Sat</th><td>140</td><td>96</td>');
    }

    public function testTheSvgRouteAnswersWithAnImage(): void
    {
        $this->get('/sales/week.svg')
            ->assertOk()
            ->assertContentType('image/svg+xml')
            ->assertSee('<svg xmlns="http://www.w3.org/2000/svg"')
            ->assertDontSee('<figure');
    }
}
```

The data table is the most stable thing to assert on: its rows hold the labels and the formatted values, independent of pixel positions. The page's `<script>` and `<link>` tags point to `/assets/charts.charts.<hash>.js` and `.css`; a test can follow them:

```php
$page = $this->get('/sales')->assertOk();
preg_match('#src="(/assets/charts\.charts\.[0-9a-f]{12}\.js)"#', $page->body(), $js);
$this->get($js[1])->assertOk();
```

## Without an app

A `Chart` needs nothing to be built and read back, which is what a test of a sales chart checks:

```php
use PHPUnit\Framework\TestCase;
use Waypoint\Charts\Chart;

final class SalesChartTest extends TestCase
{
    public function testRowsOfTheSameDayAndProductAddUp(): void
    {
        $chart = Chart::fromRows([
            ['day' => '15 Oct', 'product' => 'Croissant', 'quantity' => '88'],
            ['day' => '16 Oct', 'product' => 'Croissant', 'quantity' => 90],
            ['day' => '16 Oct', 'product' => 'Croissant', 'quantity' => 12],
            ['day' => '16 Oct', 'product' => 'Pretzel', 'quantity' => 64],
        ], x: 'day', y: 'quantity', series: 'product');

        $this->assertSame(['15 Oct', '16 Oct'], $chart->getLabels());
        $this->assertSame(
            [['name' => 'Croissant', 'values' => [88.0, 102.0]], ['name' => 'Pretzel', 'values' => [null, 64.0]]],
            $chart->getSeries()
        );
    }
}
```

To check the drawing with particular options, create an [`SvgRenderer`](../reference/Waypoint/Charts/SvgRenderer.md) with them:

```php
use Waypoint\Charts\{Chart, ChartsOptions, SvgRenderer};

$options = new ChartsOptions();
$options->format = fn(float $value, int $decimals): string => number_format($value, $decimals, ',', '.');

$svg = (new SvgRenderer($options))->renderStandalone(
    Chart::line()->labels(['Fri', 'Sat'])->series('Revenue', [1200.5, 1300])->unit('€', 2)
);

$this->assertStringContainsString('aria-label="Sat: Revenue 1.300,00 €"', $svg);
```

The output is deterministic: the same chart and options always give the same markup, so a snapshot of `render()` stays stable until the chart, the options or the plugin change.

See also: [Building charts](charts.md), [Pages, images and the script](rendering.md).
