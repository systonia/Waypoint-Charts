<?php

namespace Waypoint\Charts\Tests;

use Waypoint\Charts\ChartsPlugin;
use Waypoint\Options\RendererOptions;
use Waypoint\Testing\TestCase;

final class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = $this->app();
        $app->plugin(new ChartsPlugin());
        $app->configure(function (RendererOptions $opts) {
            $opts->directory = __DIR__ . '/views';
        });
        $app->attach([ChartsController::class]);
    }

    public function testAChartReturnedFromARouteIsAnSvgImage(): void
    {
        $this->get('/report.svg')
            ->assertOk()
            ->assertContentType('image/svg+xml')
            ->assertSee('<svg xmlns=')
            ->assertSee('<title id="wpc')
            ->assertDontSee('<figure');
    }

    public function testTheViewHelperRendersTheFigureAndTheAssetsAreServed(): void
    {
        $page = $this->get('/page')->assertOk()->assertSee('<figure class="wp-chart wp-chart-bar"')->assertSee('wp-chart-details');
        preg_match('#href="(/assets/charts\.charts\.[0-9a-f]{12}\.css)"#', $page->body(), $css);
        preg_match('#src="(/assets/charts\.charts\.[0-9a-f]{12}\.js)"#', $page->body(), $js);

        $this->get($css[1])->assertOk()->assertContentType('text/css')->assertSee('--wp-chart-series-1: #2a78d6');
        $this->get($js[1])->assertOk()->assertSee("Waypoint.directive('wp-chart-hit'");
    }
}
