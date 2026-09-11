# Entity registration compatibility entry

This directory contains the source entry for the `wc-entities` script. Entity
registration is an internal runtime concern, but this entry remains under
`public-api` because the generated script preserves an externally exposed
compatibility surface.

## Why this lives under `public-api`

The `wc-entities` script historically exposed entity helpers through the
`wc.wcEntities` browser global. Extensions may still depend on that global, so
moving the implementation does not make it safe to remove or rename its
exports.

The global and its helper exports are deprecated as of WooCommerce 11.1.0.
They remain available during the deprecation window and emit a warning when
called. Keeping the compatibility entry under `public-api` makes that
backward-compatibility obligation explicit and helps prevent the legacy
surface from being removed accidentally.

This placement does not make `entity-registration` a new supported package for
extensions. New code must not import it or use `wc.wcEntities`.

## Internal responsibilities

The entry also registers WooCommerce entities as a side effect when WordPress
loads the `wc-entities` script. That registration behavior and the pure entity
helpers under [`packages/internal/entities`](../../internal/entities) remain
internal implementation details.

Once the deprecated global completes its removal process, the registration
entry can be reassessed without implying a permanent public API.
