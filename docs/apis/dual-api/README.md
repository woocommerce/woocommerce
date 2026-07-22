---
post_title: 'Dual API (code + GraphQL)'
sidebar_label: 'Dual API'
sidebar_position: 0
---

# WooCommerce Dual API

The **dual API** is a code-first API architecture: you write plain PHP classes (the **code API**), and a build script generates a fully functional **GraphQL API** that mirrors them. The two are kept in sync from a single, manually maintained source (the code API) so there is one place to add behavior and two ways to consume it (in-process PHP calls and GraphQL-over-HTTP).

WooCommerce core ships its own dual API, but the underlying infrastructure is reusable: a plugin can define its own code API and get a matching GraphQL endpoint with the same tooling.

## Status: experimental, and a proof of concept

> **This feature is experimental.** Everything under the `Automattic\WooCommerce\Api` namespace can change in backwards-incompatible ways, or be removed, in any release. Do not use it in production extensions.

There are two separate parts to understand:

- **The infrastructure** (the build tooling, attributes, authorization model, engine-decoupling layer): Implementing a robust and stable infrastructure has been for now the main focus of the development efforts.
- **WooCommerce core's own code API** (the `coupons` and `products` queries/mutations): This is a **proof of concept**. It exists to exercise the infrastructure and will likely change significantly or be replaced in the short term. Treat it as an example, not a contract.

This dual API, both the infrastructure and the proof of concept code API, has been introduced as an experimental feature in WooCommerce 10.9.

## Requirements

- **PHP 8.1+.** The code API uses enums, named arguments, and PHP 8 attributes. On PHP 8.0 or older no GraphQL endpoint is registered.
- **The `dual_code_graphql_api` feature flag, for WooCommerce core's own endpoint only.** It is hidden (not shown on the Features settings page). Enable it with:

    ```bash
    wp option update woocommerce_feature_dual_code_graphql_api_enabled yes
    ```

The flag gates **only** WooCommerce core's `/wc/graphql` endpoint (and its proof-of-concept code API). Endpoints registered by plugins via `Main::register_graphql_endpoint()` don't require it: they are registered whenever the required infrastructure is available (see the requirements above), regardless of the flag state. A plugin that wants an on/off switch for its endpoint provides its own mechanism, or checks the flag itself (see [Gating your endpoint](./creating-a-dual-api-in-a-plugin.md#gating-your-endpoint)). Code that touches WooCommerce core's code API classes directly should still guard on `FeaturesUtil::feature_is_enabled( 'dual_code_graphql_api' )`. The settings and filters are site-wide and shared across all dual-API endpoints (see [Settings and caching](./caching-and-settings.md#scope-what-applies-where)).

## Which document do I need?

| Your question | Start here |
| --- | --- |
| What is this and how does it fit together? | [Architecture](./architecture.md) |
| How do I add to or change WooCommerce's code API? | [Extending the code API](./extending-the-code-api.md) |
| How do I paginate a list query? | [Relay-style pagination](./pagination.md) |
| How do I build my own dual API in a plugin? | [Creating a dual API in a plugin](./creating-a-dual-api-in-a-plugin.md) |
| How does authentication and authorization work? | [Authentication and authorization](./authentication-and-authorization.md) |
| How do I attach and query schema metadata? | [Metadata and discovery](./metadata.md) |
| How do I configure the endpoint and caching? | [Settings and caching](./caching-and-settings.md) |
| How do I regenerate the GraphQL code, and what is the staleness check? | [Building and staleness checks](./building-and-staleness.md) |
| The infrastructure or the builder is missing something, how do I change it safely? | [Extending the infrastructure](./extending-the-infrastructure.md) |

Reference material (lookup tables, exact signatures):

- [Recognized directories](./reference/directories.md)
- [Attributes](./reference/attributes.md)
- [Recognized methods and parameters](./reference/recognized-methods-and-parameters.md)
- [Infrastructure classes](./reference/infrastructure-classes.md)
- [Exceptions](./reference/exceptions.md)

## Audience

The primary audience for this documentation is **maintainers of WooCommerce's code API** and **developers building their own dual API in a plugin**. The secondary audience is **maintainers of the dual-API infrastructure** itself.

Throughout these docs, rules introduced as "in a plugin" generally apply equally when extending WooCommerce core's own code API; where a rule is core-only or plugin-only, that is called out explicitly.

## A working example

The [`woocommerce-simple-events`](https://github.com/woocommerce/woocommerce-simple-events) plugin is a runnable reference that exercises the infrastructure end to end: custom authentication, custom authorization attributes, granular field-level gates, pagination, scalars, and more. These docs link to it for complete, copy-pasteable examples.
