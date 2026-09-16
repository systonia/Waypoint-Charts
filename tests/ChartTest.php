<?php

namespace Waypoint\Charts\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Waypoint\Charts\Chart;

final class ChartTest extends TestCase
{
    public function testFromRowsPivotsLabelsAndSeriesInFirstSeenOrderAndSumsDuplicates(): void
    {
        $rows = [
            ['month' => '2026-01', 'sparte' => 'Inland', 'umsatz' => 100],
            ['month' => '2026-02', 'sparte' => 'Inland', 'umsatz' => '120.5'],
            ['month' => '2026-01', 'sparte' => 'Export', 'umsatz' => 40],
            ['month' => '2026-01', 'sparte' => 'Inland', 'umsatz' => 5],
            (object) ['month' => '2026-03', 'sparte' => 'Export', 'umsatz' => 70],
        ];

        $chart = Chart::fromRows($rows, x: 'month', y: 'umsatz', series: 'sparte', type: Chart::LINE);

        $this->assertSame(Chart::LINE, $chart->type());
        $this->assertSame(['2026-01', '2026-02', '2026-03'], $chart->getLabels());
        $this->assertSame([
            ['name' => 'Inland', 'values' => [105.0, 120.5, null]],
            ['name' => 'Export', 'values' => [40.0, null, 70.0]],
        ], $chart->getSeries());
    }

    public function testFromRowsWithoutASeriesColumnMakesOneUnnamedSeries(): void
    {
        $chart = Chart::fromRows([['m' => 'Jan', 'n' => 3], ['m' => 'Feb', 'n' => 4]], 'm', 'n');
        $this->assertSame([['name' => '', 'values' => [3.0, 4.0]]], $chart->getSeries());
    }

    public function testAtMostEightSeries(): void
    {
        $chart = Chart::bar();
        for ($i = 0; $i < 8; $i++) {
            $chart->series("s$i", [1]);
        }
        $this->expectException(InvalidArgumentException::class);
        $chart->series('s9', [1]);
    }

    public function testStackedOnlyAppliesToBars(): void
    {
        $this->assertTrue(Chart::bar()->stacked()->isStacked());
        $this->assertFalse(Chart::line()->stacked()->isStacked());
    }
}
