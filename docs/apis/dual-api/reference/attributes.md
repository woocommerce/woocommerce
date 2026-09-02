---
post_title: 'Reference: attributes'
sidebar_label: 'Attributes'
sidebar_position: 2
---

# Reference: attributes

PHP 8 attributes supply the metadata the builder can't infer from code structure. All built-in attributes live in `Automattic\WooCommerce\Api\Attributes`. Plugins define their own under their `Api\Attributes\` namespace, following the [conventions](#conventions-for-custom-attributes) below; those conventions also apply when adding attributes to core.

## Naming and description

| Attribute | Constructor | Targets | Purpose |
| --- | --- | --- | --- |
| `Name` | `( string $name )` | all | Override the derived GraphQL name of a type, field, query/mutation, or enum value. |
| `Description` | `( string $description )` | all | Human-readable description, surfaced in the schema. |
| `ParameterDescription` | `( string $name, string $description )` | all, repeatable | Describe a single argument by name (e.g. a computed field's `#[Parameter]`). |

## Type shaping

| Attribute | Constructor | Targets | Purpose |
| --- | --- | --- | --- |
| `ArrayOf` | `( string $type )` | all | Element type of an `array` property/return: a scalar name (`'int'`, `'string'`, `'float'`, `'bool'`) or a class name. |
| `ScalarType` | `( string $type )` | all | Render a property through a custom scalar class (e.g. `DateTime::class`). |
| `ConnectionOf` | `( string $type )` | all | Mark a `Connection` return or property as a connection of the given node type; generates `<Type>Connection`/`<Type>Edge`. |
| `ReturnType` | `( string $type )` | method | Declare the GraphQL return type when `execute()` returns an interface (PHP can't type-hint a trait). |
| `Parameter` | see below | all, repeatable | Declare an explicit argument. Used to give an output field computed arguments, or to shape/`unroll` a query argument. |
| `Unroll` | `()` | class, parameter | Expand a class's public properties into individual flat arguments instead of one input object. |

`Parameter` full signature:

```php
public function __construct(
    public readonly string $name = '',
    public readonly string $type = '',
    public readonly bool $nullable = false,
    public readonly bool $array = false,
    public readonly mixed $default = null,
    public readonly string $description = '',
    bool $has_default = false,
    public readonly bool $unroll = false,
)
```

## Lifecycle

| Attribute | Constructor | Targets | Purpose |
| --- | --- | --- | --- |
| `Deprecated` | `( string $reason )` | all | Mark a field or enum value deprecated (shown in introspection). |
| `Ignore` | `()` | all | Exclude the class or property from the schema entirely. |

## Authorization

| Attribute | Constructor | Targets | Purpose |
| --- | --- | --- | --- |
| `PublicAccess` | `()` | class, property | No authentication required. `authorize()` returns `true`. A no-op (and build warning) on a property. |
| `RequiredCapability` | `( string $capability )` | class, property, repeatable | Require a WordPress capability; `authorize( Principal $principal )` checks `user_can()`. Multiple are ANDed. |

Both can gate queries/mutations (class), output/input types (class), and output/input fields (property). A class-level gate AND-composes into every field gate of the type. See [Authentication and authorization](../authentication-and-authorization.md).

## Metadata

| Attribute | Constructor | Targets | Purpose |
| --- | --- | --- | --- |
| `Metadata` | `( string $name, bool\|int\|float\|string\|null $value )` | class, property, parameter, enum case, repeatable | Attach one name/value entry. Base class for custom categories. |
| `Internal` | `()` | class, property, enum case | `Metadata( 'internal', true )` + `[Internal] ` description prefix. |
| `Experimental` | `()` | class, property, enum case | `Metadata( 'experimental', true )` + `[Experimental] ` description prefix. |
| `HiddenFromMetadataQuery` | `()` | class, property, parameter, enum case | Omit the target from `_apiMetadata` discovery (`shows_in_metadata_query()` returns `false`). Does not affect native introspection or runtime gates. |

`Metadata` methods: `get_name()`, `get_value()`, and the overridable `transform_description( string $description ): string` (no-op in the base). Duplicate names on one target are a build error. See [Metadata and discovery](../metadata.md).

## Conventions for custom attributes

The builder recognizes custom attributes by **duck-typed conventions**, not by a base class or interface (except metadata). Declare the PHP `#[Attribute(...)]` targets you want to support.

- **Authorization attribute**: declares a public `authorize(): bool` method. Its first non-underscore parameter receives the principal; the parameter type should be the registered principal type. It may also declare the opt-in context parameters `array $_metadata`, `array $_args`, `mixed $_parent` (see [Recognized methods and parameters](./recognized-methods-and-parameters.md)). To gate fields/arguments as well as operations, include `Attribute::TARGET_PROPERTY` in the `#[Attribute(...)]` declaration.
- **Metadata attribute**: extends `Metadata` and calls `parent::__construct( $name, $value )`. Discoverable through `_apiMetadata`.
- **Description-mirroring attribute**: a `Metadata` subclass that overrides `transform_description()`. Transforms chain in source order.
- **Metadata-query opt-out**: declares `shows_in_metadata_query(): bool` returning `false` (what `#[HiddenFromMetadataQuery]` does).

If you reference an attribute without importing it, PHP resolves it to a non-existent class in the current namespace and silently ignores it; the builder emits a warning naming the FQCN it tried to load, so add the missing `use`.
