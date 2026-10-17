<?php

namespace Waypoint\Charts;

use Waypoint\Container;
use Waypoint\Html;
use Waypoint\Http\{Request, Response};
use Waypoint\Plugin\{ClientAsset, PluginBase, Renderer, ViewHelper};

/**
 * Register with `$app->plugin(new ChartsPlugin())`. Adds `$this->chart($chart)`
 * to templates, renders a Chart returned from a route as image/svg+xml, and
 * ships the stylesheet and the tooltip script.
 */
final class ChartsPlugin extends PluginBase implements ViewHelper, Renderer, ClientAsset
{
    private SvgRenderer $renderer;

    public function name(): string
    {
        return 'charts';
    }

    public function boot(Container $container, ChartsOptions $options): void
    {
        $this->renderer = new SvgRenderer($options);
        $container->set($this->renderer);
    }

    public function hooks(): array
    {
        return [$this];
    }

    public function helpers(): array
    {
        return ['chart' => fn(Chart $chart): Html => new Html($this->renderer->render($chart))];
    }

    public function supports(mixed $result): bool
    {
        return $result instanceof Chart;
    }

    public function render(mixed $result, Request $req, Response $res): void
    {
        if ($result instanceof Chart) {
            $res->withHeader('Content-Type', 'image/svg+xml; charset=utf-8')->write($this->renderer->renderStandalone($result));
        }
    }

    public function assets(): array
    {
        return ['charts.css' => __DIR__ . '/../assets/charts.css', 'charts.js' => __DIR__ . '/../assets/charts.js'];
    }
}
