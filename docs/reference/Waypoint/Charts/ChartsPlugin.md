---
title: "ChartsPlugin"
description: "Register with `$app->plugin(new ChartsPlugin())`."
order: 100
generated: true
---

`Waypoint\Charts\ChartsPlugin` · final class · namespace [`Waypoint\Charts`](index.md)

```php
final class ChartsPlugin extends \Waypoint\Plugin\PluginBase implements \Waypoint\Plugin\ClientAsset, \Waypoint\Plugin\Renderer, \Waypoint\Plugin\ViewHelper
```

Extends `Waypoint\Plugin\PluginBase`  
Implements `Waypoint\Plugin\ClientAsset`, `Waypoint\Plugin\Renderer`, `Waypoint\Plugin\ViewHelper`

Register with `$app->plugin(new ChartsPlugin())`. Adds `$this->chart($chart)`
to templates, renders a Chart returned from a route as image/svg+xml, and
ships the stylesheet and the tooltip script.

## Methods

### name()

```php
public function name(): string
```

Short unique name ('i18n'): the plan namespace for its route data, and the prefix in logs.

**Returns** `string`

### boot()

```php
public function boot(\Waypoint\Container $container, \Waypoint\Charts\ChartsOptions $options): void
```

**Parameters**

- `$container` `\Waypoint\Container`
- `$options` [`ChartsOptions`](ChartsOptions.md)

### hooks()

```php
public function hooks(): array
```

**Returns** `list<object>`: Instances of Endpoint, Fallback, RouteAttributeCompiler, Guard, ResponseHook, ArgumentBinder, Renderer, OpenApiExtension, ViewHelper, ClientAsset.

### helpers()

```php
public function helpers(): array
```

**Returns** `array<string, callable>`: name => callable

### supports()

```php
public function supports(mixed $result): bool
```

**Parameters**

- `$result` `mixed`

**Returns** `bool`

### render()

```php
public function render(mixed $result, \Waypoint\Http\Request $req, \Waypoint\Http\Response $res): void
```

**Parameters**

- `$result` `mixed`
- `$req` `\Waypoint\Http\Request`
- `$res` `\Waypoint\Http\Response`

### assets()

```php
public function assets(): array
```

**Returns** `array<string, string>`: {logical filename ending in .css or .js => absolute source path}
