---
title: Configuration and colors
description: ChartsOptions, number formats, the data table, palettes and dark mode.
order: 30
---

Settings that apply to every chart live in [`ChartsOptions`](../reference/Waypoint/Charts/ChartsOptions.md), set with `$app->configure()`:

```php
use Waypoint\Charts\ChartsOptions;

$app->configure(function (ChartsOptions $charts) {
    $charts->width = 720;
    $charts->height = 280;
    $charts->maxBarWidth = 16;
});
```

| Setting | Default | |
|---|---|---|
| `width`, `height` | `640`, `320` | drawing size in CSS pixels; `Chart::size()` overrides it per chart |
| `maxBarWidth` | `24` | bars never get wider than this many pixels |
| `format` | `null` | `Closure(float $value, int $decimals): string`; `null` formats with `number_format()` |
| `table` | `true` | the data table under every chart |
| `tableLabel` | `'Show data as table'` | the text of the `<summary>` that opens the table |
| `palette` | eight colors | series colors written into the SVG, light surface |
| `paletteDark` | eight colors | series colors of a standalone SVG in dark mode |

## Number format

`format` formats every number a chart shows: axis ticks, direct labels, tooltips, the table, the aria labels and the generated description. It receives the value and the number of decimals to show, and returns the number without unit; the chart appends the unit itself.

The default is `number_format($value, $decimals)`: `1,234.5`. German notation:

```php
$charts->format = fn (float $value, int $decimals): string => number_format($value, $decimals, ',', '.');
```

With `->unit('€', 2)` the value 1300 then reads `1.300,00 €`. With PHP's `intl` extension, the closure can use a `NumberFormatter`:

```php
$charts->format = function (float $value, int $decimals): string {
    $formatter = new NumberFormatter('de_DE', NumberFormatter::DECIMAL);
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
    return $formatter->format($value);
};
```

The closure is shared by every request of a long-running worker, so it must not capture anything of one request. It may ask a service that knows the current request's state. The bakery speaks English and German and writes the numbers of its sales charts in the visitor's language (from `app.php`):

```php
$app->configure(function (I18nOptions $i18n, ChartsOptions $charts, ExportOptions $export) use ($app) {
    // …
    $charts->format = fn (float $value, int $decimals): string => Format::number($value, $decimals, $app->get(Translator::class)->locale());
});
```

`Translator::locale()` of the [I18n plugin](https://github.com/systonia/Waypoint-I18n) keeps the locale per request, so two requests a worker serves at once each get their own. `Format::number()` (in `src/Services/Format.php`) is `number_format($value, $decimals, ',', '.')` for `de` and `number_format($value, $decimals)` otherwise.

## The data table

The `<details>` with the data table is rendered under every chart by default. It is the accessible twin of the picture and the source of the tooltip, so leave it on unless a chart has a reason to go without:

```php
$charts->table = false;                          // no table under any chart
$chart->withTable(false);                        // no table under this chart
$charts->tableLabel = 'Show the figures';         // the <summary> text
```

A chart shows its table only when both the option and the chart say so. Without a table there is no tooltip from the script; the per-position `aria-label`s remain.

## Colors

Series get colors by position: the first series the first color, and so on up to eight. The color stays with the series' position, not with its values or its rank.

The default palette is a validated colorblind-safe order of eight hues, stepped once for light and once for dark surfaces:

| Slot | Light | Dark |
|---|---|---|
| 1 | `#2a78d6` | `#3987e5` |
| 2 | `#eb6834` | `#d95926` |
| 3 | `#1baf7a` | `#199e70` |
| 4 | `#eda100` | `#c98500` |
| 5 | `#e87ba4` | `#d55181` |
| 6 | `#008300` | `#008300` |
| 7 | `#4a3aa7` | `#9085e9` |
| 8 | `#e34948` | `#e66767` |

The colors reach a chart in two ways:

- **In the SVG.** Every mark carries a `fill` or `stroke` attribute from `ChartsOptions::$palette`. That is what an SVG without stylesheet shows: an `<img>`, a PDF, an e-mail. A standalone SVG also has a `<style>` with `ChartsOptions::$paletteDark` for `prefers-color-scheme: dark`.
- **In a page.** `charts.css` sets the colors through custom properties, `--wp-chart-series-1` to `--wp-chart-series-8`, which override the attributes. Dark mode follows `prefers-color-scheme`, unless the root element has `data-theme="light"`; `data-theme="dark"` turns it on regardless.

So a different palette is set in both places: the options for SVGs on their own, the custom properties for figures on pages.

```php
$app->configure(function (ChartsOptions $charts) {
    $charts->palette = ['#1f6feb', '#d1242f', '#1a7f37', '#9a6700', '#8250df', '#bf3989', '#0969da', '#57606a'];
    $charts->paletteDark = ['#58a6ff', '#ff7b72', '#3fb950', '#d29922', '#a371f7', '#db61a2', '#79c0ff', '#8b949e'];
});
```

```css
.wp-chart {
    --wp-chart-series-1: #1f6feb; --wp-chart-series-2: #d1242f; /* … up to 8 */
}
@media (prefers-color-scheme: dark) {
    :root:where(:not([data-theme="light"])) .wp-chart {
        --wp-chart-series-1: #58a6ff; --wp-chart-series-2: #ff7b72; /* … */
    }
}
:root[data-theme="dark"] .wp-chart {
    --wp-chart-series-1: #58a6ff; --wp-chart-series-2: #ff7b72; /* … */
}
```

The selectors match those of `charts.css`, so the stylesheet that loads later wins: link yours after `pluginAssetTags()`.

A replacement palette should have eight colors that stay distinguishable for colorblind readers and against the surface; check it before shipping it. The other custom properties of `.wp-chart` set the surface and ink: `--wp-chart-surface`, `--wp-chart-text`, `--wp-chart-muted`, `--wp-chart-grid`, `--wp-chart-axis` and `--wp-chart-border`.

## Bar width

`maxBarWidth` caps the width of a bar in pixels. A bar takes its share of the slot (70 % of it, minus 2-pixel gaps between grouped bars) up to this cap:

```php
$charts->maxBarWidth = 16;   // slimmer bars on a dense dashboard
```

See also: [`ChartsOptions`](../reference/Waypoint/Charts/ChartsOptions.md), [Pages, images and the script](rendering.md).
