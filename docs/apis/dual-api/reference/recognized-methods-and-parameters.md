---
post_title: 'Reference: recognized methods and parameters'
sidebar_label: 'Methods and parameters'
sidebar_position: 3
---

# Reference: recognized methods and parameters

The builder recognizes certain method names on command and attribute classes, and certain specially named parameters that it injects at runtime. These conventions are identical for core and plugins.

## Methods on command classes (queries/mutations)

| Method | Signature | Notes |
| --- | --- | --- |
| `execute` | `execute( ...args ): <return type>` | Required. Parameters become GraphQL arguments; the return type becomes the GraphQL return type (use `#[ReturnType]` for interface returns). |
| `authorize` | `authorize( ...args ): bool` | Optional. Custom authorization for the operation; return `false` to deny. Compose with attributes via `$_preauthorized`. |

## Methods on attribute classes

| Method | Signature | Makes the attribute… |
| --- | --- | --- |
| `authorize` | `authorize( <PrincipalType> $principal, ... ): bool` | an authorization attribute. |
| `get_name` / `get_value` | `get_name(): string` / `get_value(): bool\|int\|float\|string\|null` | (on `Metadata` subclasses) expose the metadata entry. |
| `transform_description` | `transform_description( string $description ): string` | a description-mirroring metadata attribute. |
| `shows_in_metadata_query` | `shows_in_metadata_query(): bool` | able to opt its target out of `_apiMetadata` (when it returns `false`). |

## Methods on custom scalar classes

| Method | Signature | Purpose |
| --- | --- | --- |
| `serialize` | `static serialize( mixed $value ): string` | PHP value → transport string. |
| `parse` | `static parse( string $value ): mixed` | Client string → PHP value; throw `\InvalidArgumentException` on bad input. |

## Recognized parameters

These are optional, underscore-prefixed parameters detected **by name**. They may appear in any order; declare only the ones you use. The underscore prefix also keeps them out of the GraphQL argument list. (`provided_fields` on input types uses the same underscore-invisibility idea for an internal property.)

| Parameter | Type | Available on | Value |
| --- | --- | --- | --- |
| `$_principal` | the registered principal type | `execute()`, `authorize()` | The resolved principal for the request. |
| `$_preauthorized` | `bool` | `authorize()` (command) | Whether the attribute-based gates already grant access — compose your custom check on top. |
| `$_query_info` | `?array` | `execute()` | The selection tree of the current query, for resolve-time optimization. Provided via `QueryInfoExtractor`. |
| `$_metadata` | `array` | `authorize()` (attribute) | `#[Metadata]` entries at the call site, in slices `['query']`, `['type']`, `['field']` (each `array<string, scalar>`). At the operation level only `['query']` is populated. |
| `$_args` | `array` | `authorize()` (attribute) | The GraphQL arguments at the call site. |
| `$_parent` | `mixed` | `authorize()` (attribute) | The enclosing object being resolved, for output-field gates (enables owner-or-scope checks). |

For how these combine in granular authorization, see [Authentication and authorization](../authentication-and-authorization.md).

## Public PHP-side helpers

| Call | Purpose |
| --- | --- |
| `ResolverHelpers::compute_preauthorized( string $command_fqcn, object $principal ): bool` | Ask whether attribute gates would grant access, without executing the command. |
| `GraphQLControllerBase::get_schema(): SchemaHandle` | Obtain the schema handle for PHP-side metadata inspection. |
| `SchemaHandle::get_all_metadata()` / `find_metadata( ?name, ?type, ?field )` | Read collected metadata. See [Metadata and discovery](../metadata.md). |
