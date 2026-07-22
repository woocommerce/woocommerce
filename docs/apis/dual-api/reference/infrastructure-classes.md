---
post_title: 'Reference: infrastructure classes'
sidebar_label: 'Infrastructure classes'
sidebar_position: 4
---

# Reference: infrastructure classes

These classes live in `Automattic\WooCommerce\Api\Infrastructure` (and `Api\Utils`). Some are **convention classes** that ApiBuilder detects per plugin; the rest are runtime helpers. Plugin override rules apply equally when adjusting core's own behavior.

## Convention classes

ApiBuilder looks for these at `<api-namespace>\Infrastructure\*` and wires whatever it finds into the generated controller. Ship one only to diverge from the default; otherwise the default applies. The signature must match exactly.

### `ClassResolver`

```php
public static function resolve_class( string $class_name ): object
```

Instantiates command and infrastructure classes. **Default:** `wc_get_container()->get( $class_name )`. Ship your own to route through a different DI container. When no resolver is present at all, generated resolvers fall back to `new $class_name()`.

### `PrincipalResolver`

```php
public function resolve_principal(): Principal
// or
public function resolve_principal( \WP_REST_Request $request ): Principal
```

Resolves the per-request principal once. **Default:** returns `new Principal( wp_get_current_user() )` (no `$request` parameter). The **return type declares the plugin's principal type**, which the builder uses to type-check `authorize()`/`$_principal` against. Throw `UnauthorizedException`/`InvalidTokenException` to reject credentials. Anonymous requests are a resolved principal (not `null`).

### `Principal`

The default principal wraps a `WP_User`:

```php
public function __construct( public readonly \WP_User $user )
public function is_authenticated(): bool        // user->ID > 0
public function can_introspect(): bool          // user_can( $user, 'manage_woocommerce' )
public function can_use_debug_mode(): bool      // user_can( $user, 'manage_options' )
```

A custom principal can be any class. Recognized (all optional, duck-typed) methods:

| Method | If declared | If absent |
| --- | --- | --- |
| `is_authenticated(): bool` | distinguishes 401 vs 403 on denial; used by your own code | denials default to 403 (`FORBIDDEN`) |
| `can_introspect(): bool` | gates native introspection (and `_apiMetadata`, as fallback) | introspection denied |
| `can_use_debug_mode(): bool` | gates debug mode (with `_debug=1`) | debug mode denied |
| `can_query_metadata(): bool` | gates `_apiMetadata` specifically | falls back to `can_introspect()`, else deny |

Core's `Principal` deliberately omits `can_query_metadata()`, so `_apiMetadata` follows `can_introspect()`.

### `HttpStatusResolver`

```php
public function resolve_status( int $default_status, array $output, \WP_REST_Request $request ): int
```

Optional. Override the framework-computed HTTP status for any response (e.g. always 200), or return `$default_status` to defer. Called for both success and error responses. **Must not throw**: any throw is converted to a fixed 500 `INTERNAL_ERROR`. **Default:** core ships none, so its per-error-code mapping applies. See [Settings and caching](../caching-and-settings.md).

## Runtime helpers

You generally don't call these directly (generated code does), but they're public for advanced use.

### `GraphQLControllerBase`

Abstract base for the generated controller; owns the request lifecycle. Notable public members:

- `get_schema(): SchemaHandle`: schema handle for metadata inspection.
- `build_schema(): Schema\Schema`: returns the engine-decoupled wrapper, never the engine type.
- Static config accessors `get_endpoint_url()`, `get_max_query_depth()`, `get_max_query_complexity()`.

### `ResolverHelpers`

Static helpers used by generated resolvers: exception translation, pagination construction, authorization checks, and the public `compute_preauthorized( string $command_fqcn, object $principal ): bool`.

### `Main`

Bootstrap and registration:

- `is_enabled(): bool`: whether the Dual Code & GraphQL API feature is active: the infrastructure is available and the `dual_code_graphql_api` flag is on. The flag gates WooCommerce core's own endpoint only.
- `is_infrastructure_available(): bool`: whether the server can run the dual API infrastructure. That's all that plugin endpoint registration requires.
- `has_registered_plugin_endpoints(): bool`: whether some plugin has registered an endpoint during the current request.
- `register_graphql_endpoint( string $plugin_dir_or_controller_class, string $route_namespace, string $route, array $methods = ['GET','POST'] ): void`: register a plugin endpoint. Silent no-op when the infrastructure is unavailable; doesn't depend on the feature flag.
- `instantiate_graphql_controller( string $controller_class_name ): ?GraphQLControllerBase`.

### `MetadataController`, `QueryInfoExtractor`

Hand-written runtime pieces: the `_apiMetadata` field/types, and the `ResolveInfo` → `_query_info` extraction. See [Extending the infrastructure](../extending-the-infrastructure.md).

## `SchemaHandle` (`Api\Utils`)

Opaque, engine-independent handle returned by `get_schema()`:

```php
public function get_all_metadata(): array
public function find_metadata( ?string $name = null, ?string $type = null, ?string $field = null ): array
```

## Utility classes

Plain helpers (mappers, repositories) live under `Api/Utils/` and are not exposed in the schema, e.g. `Utils\Products\ProductRepository` (`find( int $id ): ?\WC_Product`, `save( \WC_Product $product ): void`). Inject them into commands via the `ClassResolver`/DI container. Plugins place their own helpers under their `Api/Utils/`.
