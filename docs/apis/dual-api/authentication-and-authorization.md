---
post_title: 'Authentication and authorization'
sidebar_label: 'Authentication/Authorization'
sidebar_position: 4
---

# Authentication and authorization

Authentication and authorization in the dual API revolve around a [**security principal**](https://en.wikipedia.org/wiki/Principal_(computer_security)): a per-request object representing who is calling. Authentication produces the principal; authorization decides what that principal may do, expressed through **attributes**.

## The principal

Each request resolves to exactly one principal, produced once by a `PrincipalResolver`. The default core resolver wraps the current WordPress user:

```php
final class PrincipalResolver {
    public function resolve_principal(): Principal {
        return new Principal( wp_get_current_user() );
    }
}
```

The default `Principal` carries the `WP_User` and exposes:

- `is_authenticated(): bool`: `true` when `user->ID > 0`. Anonymous requests are **not** signalled by `null`; they're a real principal whose user has ID 0.
- `can_introspect(): bool`: defaults to true only when the user has the `manage_woocommerce` capability.
- `can_use_debug_mode(): bool`: defaults to true only when the user has the `manage_options` capability.

Plugins authenticating against something else (app token, signed webhook, ...) ship their own `PrincipalResolver` and principal class. The resolver's **return type declares the plugin's principal type**, which ApiBuilder uses to type-check `authorize()`/`$_principal` signatures at build time. A resolver may take an optional `\WP_REST_Request $request` parameter, or none. To reject bad credentials, throw `UnauthorizedException` or `InvalidTokenException` from the resolver. See [Creating a dual API in a plugin](./creating-a-dual-api-in-a-plugin.md) and [Infrastructure classes](./reference/infrastructure-classes.md).

## Authorization attributes

Authorization is declarative. Core ships two attributes:

- `#[PublicAccess]`: no authentication required (`authorize()` always returns `true`).
- `#[RequiredCapability( 'capability-name' )]`: requires the principal to hold a WordPress capability. Repeatable; multiple capabilities are ANDed (so all the capabilities are required in the user for authorization to succeed).

```php
#[RequiredCapability( 'read_private_shop_coupons' )]
class ListCoupons { /* ... */ }
```

An attribute is recognized as an authorization attribute by **convention**: it declares a public `authorize()` method returning `bool`. The first non-underscore parameter receives the principal:

```php
public function authorize( MyPrincipal $principal ): bool { /* ... */ }
// or, for unconditional access:
public function authorize(): bool { return true; }
```

Plugins define their own authorization attributes (e.g. `#[RequiresScope( 'events:read' )]`) the same way, see the [Attributes reference](./reference/attributes.md). This is the recommended approach; it keeps authorization separate from business logic.

### The `authorize()` method on commands

For logic that doesn't fit an attribute, a query/mutation class can declare its own `authorize()` method. Compose it with the attribute decision via the `bool $_preauthorized` parameter (which will receive `true` if the attribute gates already grant):

```php
public function authorize( int $id, bool $_preauthorized, MyPrincipal $_principal ): bool {
    return $_preauthorized || $_principal->owns( $id );
}
```

## Granular (type- and field-level) authorization

Authorization attributes apply at four levels:

| Target | Effect |
| --- | --- |
| **Query / mutation** (class) | Gates the whole operation. |
| **Output type** (class) | AND-composed into every field gate of that type (including via a trait the type uses). |
| **Output field** (property) | Gates that field; re-evaluated per item when the field is a list. |
| **Input field** (property) | Gates the field, but only when it was actually provided in the request. |

`#[PublicAccess]` on a property is a no-op (it always grants) and produces a build warning.

`authorize()` methods can opt into three more context parameters, supplied per call site, detected by name, in any order:

- `array $_metadata`: `#[Metadata]` entries visible at the call site, in up to three slices: `['query']` (originating operation), `['type']` (enclosing type), `['field']` (the gated field).
- `array $_args`: the GraphQL arguments at the call site.
- `mixed $_parent`: the enclosing object being resolved (for an output-field gate, the parent object; lets you implement owner-or-scope checks).

```php
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY )]
final class OwnerOrScope {
    public function __construct( public readonly string $scope ) {}

    public function authorize( EventsPrincipal $principal, mixed $_parent ): bool {
        return $principal->has_scope( $this->scope )
            || ( is_object( $_parent ) && $_parent->organizer_login === $principal->user_login );
    }
}
```

### Deny shape and HTTP status

When a gate denies:

- **Operation-level** denies produce the bare authorization error.
- **Field-level** denies attach `extensions.subject = { type, field, attribute }` alongside the preserved `extensions.code`.

The error code and HTTP status depend on whether the principal is authenticated:

- **Anonymous** principal (`is_authenticated()` returns `false`) → `UNAUTHORIZED` / **401** (authenticating might help).
- **Authenticated** principal, or one that doesn't expose `is_authenticated()` → `FORBIDDEN` / **403** (authenticating won't help).

Credential problems surfaced by the resolver use `UNAUTHORIZED` (401) or `INVALID_TOKEN` (401). See [Exceptions](./reference/exceptions.md).

## Introspection, debug mode, and metadata gating

Three sensitive surfaces are gated independently, each by a combination of a principal method, a filter, and a fail-closed default:

| Surface | Principal method | Filter | Default if method absent |
| --- | --- | --- | --- |
| Native introspection (`__schema`, `__type`) | `can_introspect()` | `woocommerce_graphql_can_introspect` | deny |
| Debug mode (also requires `_debug=1`) | `can_use_debug_mode()` | `woocommerce_graphql_can_use_debug_mode` | deny |
| `_apiMetadata` discovery | `can_query_metadata()`, else falls back to `can_introspect()` | `woocommerce_graphql_can_query_metadata` | deny |

All three gates **fail closed**:

- A `null`/unresolved principal denies.
- The principal method's return is checked with `=== true` (a truthy non-bool denies).
- A throw from the method or filter is caught and treated as a deny.
- Filters must return strictly `true` to grant; loose values like `1` or `'yes'` deny.

The filters receive `( bool $decision, ?object $principal, \WP_REST_Request $request )`. They are **not** invoked when principal resolution itself failed. They are also **site-wide**: a callback affects every dual-API endpoint on the site (core and plugins), so branch on the `$request` route if it should apply to only one; see [Scope: what applies where](./caching-and-settings.md#scope-what-applies-where). The core `Principal` declares `can_introspect()` (gated on `manage_woocommerce`), which also governs `_apiMetadata` since it has no `can_query_metadata()` - so admin access to both works out of the box, and other principals are denied unless they opt in.

Example override:

```php
add_filter(
    'woocommerce_graphql_can_introspect',
    fn( bool $can, $principal, \WP_REST_Request $request ): bool =>
        $can || 'true' === $request->get_param( 'x-allow-introspection' ),
    10,
    3
);
```

## Pre-authorization for code-API callers

Code that calls the code API directly (not through GraphQL) can ask whether the attribute gates would grant access for a principal, without executing the command, via `ResolverHelpers::compute_preauthorized( string $command_fqcn, object $principal ): bool`.
