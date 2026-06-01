---
post_title: 'Metadata and discovery'
sidebar_label: 'Metadata'
sidebar_position: 5
---

# Metadata and discovery

The dual API can attach machine-readable **metadata** to schema elements (types, fields, arguments, enum values) and expose it for discovery. The first built-in uses are marking elements as internal or experimental, but the mechanism is general: plugins ship their own categories without infrastructure changes.

## Attaching metadata

The base `#[Metadata( name, value )]` attribute attaches one name/value entry. It is repeatable and targets classes, properties, parameters, and enum cases. Values are restricted to `bool|int|float|string|null`.

```php
#[Metadata( 'owner', 'payments-team' )]
#[Metadata( 'beta', true )]
class SomeType { /* ... */ }
```

Core ships two convenience subclasses:

- `#[Internal]` — `name = 'internal'`, `value = true`. For WooCommerce-core-only elements.
- `#[Experimental]` — `name = 'experimental'`, `value = true`.

Duplicate names on the same target are a build-time error (no silent merge or last-wins). Type-level metadata is **not** auto-propagated to fields; consumers apply the "subfields inherit" rule themselves if they want it.

## Description mirroring

A metadata subclass can mirror its marking into the human-readable description, so it's visible in tools (like stock GraphiQL) that don't know about the discovery channel. Override `transform_description()`:

- `#[Internal]` prefixes the description with `[Internal] ` and supplies a default body when none exists.
- `#[Experimental]` does the same with `[Experimental] `.

When several transforming attributes apply to one element, their transforms chain in PHP source order (last-in-source wraps outermost), and the text flows through the standard `__( ..., 'woocommerce' )` translation pipeline. The plain `#[Metadata]` base does not modify descriptions. To define your own description-mirroring category, subclass `Metadata` and override `transform_description()`; see the [Attributes reference](./reference/attributes.md).

## Discovery via GraphQL: `_apiMetadata`

Every generated schema gains a root field:

```graphql
_apiMetadata(name: String, type: String, field: String, attribute: String): [MetadataTarget!]!
```

Each `MetadataTarget` carries two parallel slices: the collected metadata `entries`, and an `authorization` slice describing the authorization gates on that target. Arguments narrow independently (combined with AND): `name` trims surviving rows to the matching metadata entry, and `attribute` trims the authorization slice to a specific attribute short name.

### Access is gated

`_apiMetadata` is gated like introspection, see [Authentication and authorization](./authentication-and-authorization.md). The resolver consults `can_query_metadata()` on the principal if present, otherwise falls back to `can_introspect()`, otherwise denies; the `woocommerce_graphql_can_query_metadata` filter can override. This prevents anonymous callers from enumerating the schema's authorization gates.

### Opting a target out

Apply `#[HiddenFromMetadataQuery]` to a class or property to omit it (and its descriptors) from `_apiMetadata`. This is recognized by a duck-typed `shows_in_metadata_query(): bool` returning `false`; a target's visibility is the AND of that method across all its attributes. It does **not** affect native introspection or the runtime authorization gates: an attribute hidden from discovery still runs its `authorize()`.

## Discovery via PHP: `SchemaHandle`

For in-process inspection, `GraphQLControllerBase::get_schema()` returns an opaque `SchemaHandle` (`Automattic\WooCommerce\Api\Utils\SchemaHandle`) with:

- `get_all_metadata(): array`: every metadata row in the schema.
- `find_metadata( ?string $name, ?string $type, ?string $field ): array`: the same filter-narrows semantics as the GraphQL field.

The handle never exposes the underlying engine type in its public signature, so PHP callers don't depend on the GraphQL engine. It's the natural home for future schema-inspection operations.
