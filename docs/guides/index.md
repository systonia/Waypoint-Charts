---
title: Guides
description: One part of Waypoint Charts per page, with examples that run as they are.
order: 10
---

Each guide covers one part of the plugin. The examples assume the app from the [start page](../index.md): `ChartsPlugin` registered, a layout with `$this->waypointJsTag()` and `$this->pluginAssetTags()`. The [reference](../reference/index.md) lists every class and method.

- [Building charts](charts.md): bar and line charts, series, gaps, stacking, units, sizes, charts from query rows
- [Pages, images and the script](rendering.md): the view helper, charts as `image/svg+xml`, the tooltip script, accessibility, rendering outside a view
- [Configuration and colors](configuration.md): `ChartsOptions`, number formats, the data table, palettes, dark mode
- [Testing](testing.md): charts in feature tests and unit tests
