<?php

namespace Waypoint\Charts\Tests;

use PHPUnit\Framework\TestCase;
use Waypoint\Charts\{Chart, ChartsOptions, SvgRenderer};

final class SvgRendererTest extends TestCase
{
    private function renderer(?callable $configure = null): SvgRenderer
    {
        $options = new ChartsOptions();
        if ($configure !== null) {
            $configure($options);
        }
        return new SvgRenderer($options);
    }

    private function twoSeriesBars(): Chart
    {
        return Chart::bar()->title('Revenue <2026>')->labels(['Jan', 'Feb', 'Mar'])
            ->series('Domestic', [120, 140, 165])->series('Export', [40, 55, 70])->unit('€');
    }

    public function testFigureHasAccessibleSvgLegendAndTable(): void
    {
        $html = $this->renderer()->render($this->twoSeriesBars());

        $this->assertStringContainsString('<figure class="wp-chart wp-chart-bar" wp-chart data-series="2">', $html);
        $this->assertStringContainsString('<figcaption class="wp-chart-title">Revenue &lt;2026&gt;</figcaption>', $html);
        $this->assertMatchesRegularExpression('#<svg [^>]*role="img" aria-labelledby="wpc[0-9a-f]{8}-title wpc[0-9a-f]{8}-desc"#', $html);
        $this->assertStringContainsString('<title id="wpc', $html);
        $this->assertStringContainsString('Highest value 165 € at Mar (Domestic).', $html);
        $this->assertSame(6, substr_count($html, 'class="wp-chart-mark wp-chart-bar'));
        $this->assertSame(2, substr_count($html, '<ul class="wp-chart-legend">') + substr_count($html, '<li><svg class="wp-chart-key" width="12"') - 1);
        $this->assertStringContainsString('<details class="wp-chart-details"><summary>Show data as table</summary>', $html);
        $this->assertStringContainsString('<tr><th scope="row">Feb</th><td>140 €</td><td>55 €</td></tr>', $html);
        $this->assertStringContainsString('aria-label="Mar: Domestic 165 €, Export 70 €"', $html);
        $this->assertStringNotContainsString('wp-chart-label', $html, 'no direct label on a multi-series bar chart');
    }

    public function testBarsUseThePaletteInOrderWithARoundedDataEndAndASquareBaseline(): void
    {
        $html = $this->renderer()->render($this->twoSeriesBars());

        $this->assertStringContainsString('fill="#2a78d6" data-index="0" data-series="0"', $html);
        $this->assertStringContainsString('fill="#eb6834" data-index="0" data-series="1"', $html);
        preg_match('/wp-chart-series-1" d="([^"]+)"/', $html, $m);
        $this->assertStringStartsWith('M', $m[1]);
        $this->assertSame(2, substr_count($m[1], 'Q'), 'two rounded corners at the top');
        $this->assertMatchesRegularExpression('/L[\d.]+,([\d.]+)L[\d.]+,\1Z$/', $m[1], 'the baseline edge is a straight line');
    }

    public function testASingleSeriesGetsNoLegendButADirectLabelOnTheMaximum(): void
    {
        $html = $this->renderer()->render(Chart::bar()->labels(['a', 'b'])->series('Only', [3, 9])->unit('%'));

        $this->assertStringNotContainsString('wp-chart-legend', $html);
        $this->assertStringContainsString('<text class="wp-chart-label"', $html);
        $this->assertStringContainsString('>9%</text>', $html);
    }

    public function testStackedBarsLeaveASurfaceGapAndRoundOnlyTheOutermostSegment(): void
    {
        $html = $this->renderer()->render(Chart::bar()->stacked()->labels(['a'])->series('x', [10])->series('y', [10])->size(200, 200));

        preg_match_all('/wp-chart-bar wp-chart-series-(\d)" d="([^"]+)"/', $html, $m, PREG_SET_ORDER);
        $this->assertCount(2, $m);
        $bottom = $m[0][2];
        $top = $m[1][2];
        $this->assertStringNotContainsString('Q', $bottom, 'inner segment has no rounded corners');
        $this->assertSame(2, substr_count($top, 'Q'));
    }

    public function testLinesBreakAtNullsCarryMarkersAndEndLabels(): void
    {
        $html = $this->renderer()->render(Chart::line()->labels(['a', 'b', 'c', 'd'])->series('S', [1, null, 3, 4])->unit('', 1));

        preg_match('/wp-chart-line wp-chart-series-1" d="([^"]+)"/', $html, $m);
        $this->assertSame(2, substr_count($m[1], 'M'), 'a null starts a new segment');
        $this->assertSame(3, substr_count($html, '<circle class="wp-chart-marker'));
        $this->assertStringContainsString('stroke="#fcfcfb" stroke-width="2"', $html, 'markers wear a surface ring');
        $this->assertStringContainsString('>4.0</text>', $html, 'the end value is labelled');
        $this->assertStringContainsString('class="wp-chart-crosshair"', $html);
    }

    public function testCollidingEndLabelsAreSkippedRatherThanStacked(): void
    {
        $html = $this->renderer()->render(Chart::line()->labels(['a', 'b'])->series('A', [10, 20])->series('B', [11, 20.4]));

        $this->assertSame(1, substr_count($html, 'class="wp-chart-label"'));
    }

    public function testXLabelsAreThinnedWhenTheyWouldOverlap(): void
    {
        $labels = array_map(fn (int $i): string => "2026-01-" . str_pad((string) $i, 2, '0', STR_PAD_LEFT), range(1, 30));
        $html = $this->renderer()->render(Chart::line()->labels($labels)->series('n', array_fill(0, 30, 1))->size(320, 200));

        $shown = substr_count($html, 'text-anchor="middle"');
        $this->assertGreaterThan(1, $shown);
        $this->assertLessThan(30, $shown);
    }

    public function testCustomFormatterAndUnitPlacement(): void
    {
        $html = $this->renderer(function (ChartsOptions $o) {
            $o->format = fn (float $v, int $d): string => number_format($v, $d, ',', '.');
        })->render(Chart::bar()->labels(['x'])->series('n', [1234.5])->unit('€', 2));

        $this->assertStringContainsString('<td>1.234,50 €</td>', $html);
        $this->assertStringContainsString('>1.234,50 €</text>', $html);
    }

    public function testStandaloneSvgCarriesItsOwnDarkModeStyle(): void
    {
        $svg = $this->renderer()->renderStandalone($this->twoSeriesBars());

        $this->assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('<style>@media (prefers-color-scheme: dark){.wp-chart-series-1{fill:#3987e5', $svg);
        $this->assertStringNotContainsString('<figure', $svg);
    }

    public function testTableCanBeSwitchedOffPerChartOrGlobally(): void
    {
        $this->assertStringNotContainsString('wp-chart-details', $this->renderer()->render($this->twoSeriesBars()->withTable(false)));
        $this->assertStringNotContainsString('wp-chart-details', $this->renderer(fn (ChartsOptions $o) => $o->table = false)->render($this->twoSeriesBars()));
    }
}
