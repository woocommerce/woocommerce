---
post_title: 'Settings and caching'
sidebar_label: 'Settings and caching'
sidebar_position: 6
---

# Settings and caching

WooCommerce core's GraphQL endpoint is configured under **WooCommerce → Settings → Advanced → GraphQL**. The section appears when some dual-API endpoint is active on the site: WooCommerce core's (the `dual_code_graphql_api` feature flag is on) or at least one registered by a plugin. The **Endpoint URL** setting, which only configures core's endpoint, is shown only when the feature flag is on.

These settings are **site-wide, not per-endpoint**: every setting below except **Endpoint URL** applies to *every* dual-API endpoint on the site, including those registered by plugins. See [Scope: what applies where](#scope-what-applies-where).

## Settings

| Setting | Option name (`Main::` constant) | Type | Default | Effect |
| --- | --- | --- | --- | --- |
| Endpoint URL | `woocommerce_graphql_endpoint_url` (`OPTION_ENDPOINT_URL`) | text | `wc/graphql` | **Core's `/wc/graphql` only.** Path under `/wp-json/`. Must be at least two segments (`namespace/route`); validated and normalized on save. Plugins set their own route when they register an endpoint, so this setting does not affect them. |
| Enable GET endpoint | `woocommerce_graphql_get_endpoint_enabled` (`OPTION_GET_ENDPOINT_ENABLED`) | checkbox | `yes` | When off, the endpoint accepts POST only; GET returns 404. Mutations are always rejected over GET. |
| Maximum query depth | `woocommerce_graphql_max_query_depth` (`OPTION_MAX_QUERY_DEPTH`) | number | `15` | Rejects queries nested deeper than this during validation. Falls back to default when unset or non-positive. |
| Maximum query complexity | `woocommerce_graphql_max_query_complexity` (`OPTION_MAX_QUERY_COMPLEXITY`) | number | `1000` | Rejects queries whose computed complexity score exceeds this. Connection fields multiply child cost by page size. |
| Parsed query cache TTL | `woocommerce_graphql_query_cache_ttl` (`OPTION_QUERY_CACHE_TTL`) | number | `86400` | Seconds before cached parsed queries expire (object cache and APQ paths). |
| Enable OPcache-based caching | `woocommerce_graphql_opcache_enabled` (`OPTION_OPCACHE_ENABLED`) | checkbox | `yes` | Cache parsed ASTs as PHP files served from OPcache shared memory. |
| Enable ObjectCache-based caching | `woocommerce_graphql_object_cache_enabled` (`OPTION_OBJECT_CACHE_ENABLED`) | checkbox | `yes` | Cache parsed ASTs in the WP object cache. |
| Enable APQ caching | `woocommerce_graphql_apq_enabled` (`OPTION_APQ_ENABLED`) | checkbox | `yes` | Support the Apollo Automatic Persisted Queries protocol (`persistedQuery` extension). When off, hash-only requests are rejected. |

The depth and complexity metrics are observable on a request by appending `?_debug=1` (when the principal may use debug mode); the response carries `extensions.debug.depth` and `extensions.debug.complexity`.

## Scope: what applies where

The dual API has one set of switches and filters shared by every endpoint on the site, there is no per-plugin configuration surface. Concretely:

- **The `dual_code_graphql_api` feature flag gates WooCommerce core's endpoint only.** When it's off, core's `/wc/graphql` is not registered, but plugin endpoints (registered through `Main::register_graphql_endpoint()`) are unaffected: they only require the dual API infrastructure to be available. See [Gating your endpoint](./creating-a-dual-api-in-a-plugin.md#gating-your-endpoint).
- **Every setting except Endpoint URL applies to all endpoints.** The GET toggle, max depth, max complexity, the three caching toggles, and the cache TTL are read from the shared infrastructure, so a plugin endpoint honours them exactly as core's does (for example, plugin endpoints reject GET when the GET toggle is off). **Endpoint URL is the exception**: it only configures core's `/wc/graphql`; a plugin chooses its own route at registration.
- **The filters below are global.** A callback added to any of them affects *every* dual-API endpoint on the site, core and plugins alike. Each filter receives the `\WP_REST_Request`, so a callback that should apply to only one endpoint must branch on the request's route itself.

## Query caching

Parsing a GraphQL query into an AST is the expensive, repeatable step, so the framework caches parsed ASTs. On each request the resolution chain is:

1. **OPcache file backend**: when its toggle is on, the OPcache extension is loaded, and the cache directory is writable. Parsed ASTs are written as `return [...];` PHP files under `wp-content/uploads/wc-graphql-cache/v<engine-version>/`; OPcache serves them as compiled bytecode (no string parse, no `unserialize`, no remote cache call).
2. **WP object cache**: otherwise, when its toggle is on.
3. **No cache**: parse on every request.

Notes:

- The cache key/version is tied to the query string and the parser version, so there's no correctness TTL concern on the file backend; the configurable TTL applies to the object-cache and APQ paths.
- OPcache writes are atomic (temp file + `rename()`), drop a deny-all `.htaccess`, and pre-warm the bytecode. Expired files are cleaned up via a scheduled `woocommerce_graphql_opcache_cleanup` action.
- APQ always uses the object cache for hash-only lookups, regardless of the standard-query toggles, preserving persisted-query semantics.

## Relevant filters

| Filter | Signature | Purpose |
| --- | --- | --- |
| `woocommerce_graphql_opcache_cache_dir` | `( string $dir )` | Override the OPcache file directory (default `{uploads}/wc-graphql-cache/v<n>`). Empty strings and stream wrappers are rejected. |
| `woocommerce_graphql_can_introspect` | `( bool, ?object $principal, \WP_REST_Request )` | Gate native introspection. See [Authentication and authorization](./authentication-and-authorization.md). |
| `woocommerce_graphql_can_use_debug_mode` | `( bool, ?object $principal, \WP_REST_Request )` | Gate debug mode. |
| `woocommerce_graphql_can_query_metadata` | `( bool, ?object $principal, \WP_REST_Request )` | Gate `_apiMetadata`. See [Metadata](./metadata.md). |

## Customizing the response HTTP status

A plugin can override the HTTP status of any response (for example, always return 200) by shipping an `HttpStatusResolver` convention class. Core ships none, so its per-error-code mapping is the default. See [Creating a dual API in a plugin](./creating-a-dual-api-in-a-plugin.md) and [Infrastructure classes](./reference/infrastructure-classes.md).
