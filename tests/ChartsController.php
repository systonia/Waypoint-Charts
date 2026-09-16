<?php

namespace Waypoint\Charts\Tests;

use Waypoint\Attributes\{Controller, Get};
use Waypoint\Charts\Chart;
use Waypoint\Http\{Request, View};

#[Controller]
class ChartsController
{
    #[Get('/report.svg')]
    public function svg(): Chart
    {
        return Chart::line()->title('Report')->labels(['a', 'b'])->series('S', [1, 2]);
    }

    #[Get('/page')]
    public function page(Request $req): View
    {
        return new View('Page', ['chart' => Chart::bar()->title('Inline')->labels(['a'])->series('S', [1])], partial: $req->acceptPartial);
    }
}
