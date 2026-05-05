# Inner Block Protocols

> **Experimental:** These protocols are internal and subject to change without notice. Block context keys, action names, and type shapes documented here should not be considered a stable public API. They may be renamed, restructured, or removed in future releases.

## Overview

This document defines the **context protocol pattern** used by WooCommerce blocks to let reusable inner blocks work with any parent store. Three concrete protocols exist today:

| Context Key | Purpose | Inner Blocks |
| --- | --- | --- |
| `woocommerceSelectableItems` | Select/deselect items (filters, variations) | checkbox-list, chips |
| `woocommerceRemovableItems` | Remove individual items (active filters) | removable-chips |
| `woocommerceRangeInput` | Numeric range input (price, slider) | price-slider |

Each protocol follows the same pattern — only item shape and action names differ.

## Problem Statement

WooCommerce blocks need reusable UI components (chips, swatches, pills, sliders) that can work inside multiple parent blocks with different Interactivity API stores. Without a protocol, inner blocks get tightly coupled to a single store namespace, preventing true reusability.

## Solution: Context Protocol Pattern

Inner blocks become **presentational** — they read a standardized context protocol and call parent-provided actions instead of directly referencing a specific store.

```text
┌─────────────────────────────────────────────────────────┐
│  Protocol Specification (this document)                 │
│  └── Defines the contract both sides must follow        │
├─────────────────────────────────────────────────────────┤
│  Parent Block (implements protocol)                     │
│  ├── Registers own Interactivity store                  │
│  ├── Provides context matching protocol shape           │
│  ├── Implements fixed-name actions/getters              │
│  └── Handles business logic (filtering, variation, …)   │
├─────────────────────────────────────────────────────────┤
│  Reusable Inner Block (consumes protocol)               │
│  ├── Reads context per protocol specification           │
│  ├── Renders UI based on context data                   │
│  ├── Binds fixed-name actions/getters                   │
│  └── Zero knowledge of parent's store/business logic    │
└─────────────────────────────────────────────────────────┘
```

## Shared Conventions

All three protocols follow these rules:

- **Context key** prefixed with `woocommerce` (e.g. `woocommerceSelectableItems`)
- **`storeNamespace`** field on every context object — tells inner block which parent store to resolve `actions.*` / `state.*` against
- **Fixed action & getter names** (not configurable via context fields) — inner blocks hardcode them
- **TS contract interface** (`*ParentStore`) — parents assert conformance via `satisfies`
- **Items-carrying contexts** (`selectableItems`, `removableItems`) — parent exposes items via a protocol-named getter (`state.selectableItems`, `state.removableItems`). Generic `state.items` is intentionally avoided so multiple protocols can coexist on the same store namespace without collision
- **Nested `data-wp-interactive`** — inner blocks keep an outer scope under their own namespace (show-more and other presentational state) and nest an inner region under the parent namespace for `data-wp-each` + selection bindings. Presentational bindings on iterated items use cross-namespace `::` syntax back to the inner store (e.g. `data-wp-bind--hidden="<own-ns>::state.itemHidden"`). Inner store reads iteration context via `getContext(storeNamespace)` — no hardcoded parent reference
- **SSR fallback via `data-wp-each-child`** — PHP renders the initially visible items (first `displayLimit`) once with `data-wp-each-child`, each carrying its own `data-wp-context` + live bindings so hydration wires them up. The template handles the remaining items client-side
- **Inner block owns show-more** — default `displayLimit = 15`. Inner block exposes `state.itemHidden` (reads iteration `context.item.index` via cross-namespace `getContext(storeNamespace)`) and renders the show-more button. Parent never knows about show-more

## Enforcement via TypeScript `satisfies`

Every protocol ships a `*ParentStore` interface. Parent stores assert:

```typescript
import type { SelectableItemsParentStore } from '../../types/type-defs/selectable-items';
// or: RemovableItemsParentStore, RangeInputParentStore

myStore satisfies SelectableItemsParentStore;
```

Missing method/getter → compile error. No runtime cost.

---

## Protocol: Selectable Items

### Context Key

```text
woocommerceSelectableItems
```

Items are dynamic (computed at render time from database queries), so parent blocks do **not** use `providesContext` in block.json. Instead, they pass context directly when rendering inner blocks:

```php
// Parent block render():
( new \WP_Block( $parsed_block, array(
    'woocommerceSelectableItems' => $context,
) ) )->render();
```

In the editor, parent blocks use `BlockContextProvider` to pass the same data:

```jsx
<BlockContextProvider value={ { 'woocommerceSelectableItems': context } }>
    { children }
</BlockContextProvider>
```

#### Inner block.json (consumer)

Inner blocks declare the context key they consume via `usesContext`, and which parents they can be nested inside via `ancestor`:

```json
{
  "name": "woocommerce/product-filter-checkbox-list",
  "usesContext": ["woocommerceSelectableItems"],
  "ancestor": [
    "woocommerce/product-filter-attribute",
    "woocommerce/product-filter-status",
    "woocommerce/product-filter-taxonomy",
    "woocommerce/product-filter-rating"
  ]
}
```

Inner blocks receive the protocol data through `$block->context['woocommerceSelectableItems']` in PHP.

### SelectableItemsContext

The context object that parents MUST provide. Typed as `SelectableItemsContext<T>` where `T` is the extra fields the parent adds to each item (default: `unknown`).

#### Core Fields (Required)

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `items` | `SelectableItem<T>[]` | **Yes** | Items to render |
| `selectionMode` | `'single' \| 'multiple'` | **Yes** | Selection behavior |
| `storeNamespace` | `string` | **Yes** | Parent's Interactivity API store |

#### Accessibility Fields (Optional)

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `groupLabel` | `string` | No | Screen reader label for the group. Rendered as `<legend>` in fieldset. Example: "Filter by Color" |

#### Presentation Fields (Optional)

| Field | Type | Default | Description |
| --- | --- | --- | --- |
| `isLoading` | `boolean` | `false` | Parent is fetching items. Inner blocks show skeleton/loading state. |
| `filterType` | `string` | `undefined` | Domain discriminator that inner blocks may use to vary presentation. For example, `'rating'` unlocks star rendering in `checkbox-list`. Values are parent-defined; unknown values fall back to text. |

### SelectableItem

`SelectableItem<T = unknown>` — base fields plus an optional generic extension `T` for domain-specific data.

Each item in the `items` array MUST have:

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `id` | `string` | **Yes** | Unique identifier for DOM element id. Format: `"{type}-{value}"` e.g. `"attribute-red"` |
| `label` | `string \| HTML` | **Yes** | Display text or HTML (swatches, rating stars). HTML labels are emitted by the SSR `foreach` and preserved by `data-wp-each` via stable keys. |
| `value` | `string` | **Yes** | Value for selection/submission |
| `ariaLabel` | `string` | Conditional | **Required** if `label` contains HTML |
| `selected` | `boolean` | No | Current selection state (default: false). SSR hint only — parent's `state.selectableItems` derives the live `selected` used for bindings. |
| `disabled` | `boolean` | No | Whether item can be selected (default: false) |
| `type` | `string` | No | Type discriminator (e.g., `"attribute/color"`) |

Extra fields go in `T`. For product filters, `T = FilterItemFields`:

```typescript
type FilterItemFields = {
  count: number;
  termId?: number;
  parent?: number;
  depth?: number;
  menuOrder?: number;
};

type FilterOptionItem = SelectableItem<FilterItemFields>;
```

Inner blocks typed against a specific `T` access extra fields type-safely. Built-in inner blocks ignore unknown fields.

### Parent Store Requirements

The store registered under `storeNamespace` MUST expose:

| Name | Kind | Contract |
| --- | --- | --- |
| `state.selectableItems` | getter | Returns iterable of items with `selected: boolean` and `index: number` derived. Items come from `getServerContext().items` (so they refresh after navigation) merged with the client SSOT (`activeFilters`). Reactive — re-evaluates when SSOT changes. |
| `actions.toggle` | action | Toggles selection for the target item. Accepts an optional `item` argument (used when an inner block proxies the call via its own store); when omitted, falls back to `getContext().item`. Mutates parent's SSOT (e.g. `activeFilters`). |

Fixed names (not configurable). The getter is `selectableItems` (not `items`) to avoid colliding with other protocols (`removableItems`, etc.) when multiple protocols live on the same store namespace.

Inner blocks iterate this getter directly via `data-wp-each--item="state.selectableItems"` inside a region that nests `data-wp-interactive="<parent-ns>"` (so `context.item` is set under the parent namespace, matching where `actions.toggle` and `context.item.selected` expect it). See _Inner Block Own Store_ below.

Enforcement via TypeScript contract:

```typescript
import type { SelectableItemsParentStore } from '../../types/type-defs/selectable-items';

const myStore = {
	state: { get selectableItems() { /* derive selected */ }, /* ... */ },
	actions: { toggle: ( item? ) => { /* target item, mutate SSOT */ }, /* ... */ },
};

// Compile-time check — TS error if `state.selectableItems` or `actions.toggle` is missing/wrong-shaped.
myStore satisfies SelectableItemsParentStore;
```

### Selection State Model

- **SSOT** lives in parent's domain state (e.g. `context.activeFilters` for filters).
- **Items rendered via `data-wp-each`** iterating the parent's `state.selectableItems` inside a nested-namespace region. PHP `foreach` with `data-wp-each-child` + per-item `data-wp-context` (including `index`) provides an SSR fallback for the items visible on first paint (first `displayLimit`); the rest are rendered client-side via the template.
- **`item.selected`** on raw items (in `getContext().items`) is only a PHP SSR hint. Parent's `state.selectableItems` re-derives `selected` from SSOT for the live binding source.
- **`actions.toggle`** mutates SSOT only. Never touches raw `item.selected`. It reads `getContext().item` which is set by `data-wp-each` under the parent namespace (the items region switches to the parent namespace precisely so this works).

External mutations (active-filter removal, cross-block sync) flow through automatically: mutate `activeFilters` → `state.selectableItems` re-evaluates → every `context.item.selected` binding updates across all blocks.

### Rendering Rules

Inner blocks SHOULD:

1. Render `<input type="radio">` when `selectionMode === 'single'`
2. Render `<input type="checkbox">` when `selectionMode === 'multiple'`
3. Set `disabled` attribute and apply reduced-opacity styling when `item.disabled === true`
4. Use `groupLabel` for fieldset legend (screen reader accessible)
5. Show skeleton/loading UI when `isLoading === true`
6. Show items up to `displayLimit` (default 15), render show-more button when exceeded

Inner blocks typed against `FilterItemFields` MAY additionally:

1. Show counts when `item.count` exists

---

## Design Rationale

### Generic Extension Pattern

`SelectableItem<T>` uses a generic parameter instead of a flat union of optional fields:

- Base fields are shared by all consumers (id, label, value, selected, disabled, type)
- Domain-specific fields live in `T` — typed, not untyped `[key: string]: unknown`
- Filter blocks use `FilterOptionItem = SelectableItem<FilterItemFields>` with count, parent, depth, etc.
- A variation selector would use `SelectableItem<{ price?: string; stockStatus?: string }>` etc.
- TypeScript enforces correct shape at each call site with no extra runtime cost

### Backward Compatibility

`SelectableItem<T>` replaces the old flat `FilterOptionItem`. Key changes:

| Old `FilterOptionItem` | New `SelectableItem<FilterItemFields>` |
| --- | --- |
| `id?: number` (optional, number) | `id: string` (required, string — used for DOM element id) |
| `count: number` (required) | `count: number` in `FilterItemFields` (required for filters, absent for other consumers) |
| No `disabled` | `disabled?: boolean` on base type |
| No `type` | `type?: string` on base type |

---

## Type Definitions

This section provides copy-paste-ready type definitions for both TypeScript and PHP. These definitions enforce the protocol specification above.

### TypeScript

Location: `assets/js/types/type-defs/selectable-items.ts`

```typescript
import type { ReactNode } from 'react';

export type SelectableItem< T = unknown > = (
	| { label: string; ariaLabel?: string }
	| { label: ReactNode; ariaLabel: string }
) & {
	/** Unique key for DOM element id. Format: "{type}-{value}" */
	id: string;
	value: string;
	selected?: boolean;
	disabled?: boolean;
	type?: string;
} & T;

export interface SelectableItemsContext< T = unknown > {
	items: SelectableItem< T >[];
	selectionMode: 'single' | 'multiple';
	storeNamespace: string;
	groupLabel?: string;
	isLoading?: boolean;
	filterType?: string;
}

export type SelectableItemsBlockContext< T = unknown > = {
	'woocommerceSelectableItems': SelectableItemsContext< T >;
};

export interface SelectableItemsParentStore< T = unknown > {
	state: {
		selectableItems: readonly SelectableItem< T >[];
	};
	actions: {
		toggle: ( item?: SelectableItem< T > ) => void;
	};
}
```

Filter blocks extend with `FilterItemFields` (from `product-filters/types.ts`):

```typescript
export type FilterItemFields = {
	count: number;
	termId?: number;
	parent?: number;
	depth?: number;
	menuOrder?: number;
};

export type FilterOptionItem = SelectableItem< FilterItemFields >;
```

Inner blocks are typed via `SelectableItemsBlockContext<FilterItemFields>`:

```typescript
// In checkbox-list/types.ts or chips/types.ts
export type EditProps = BlockEditProps< BlockAttributes > & {
	context: SelectableItemsBlockContext< FilterItemFields >;
	// ...color props
};
```

### PHP

No base class or trait needed — parent blocks set `$block->context` directly. PHPStan type aliases (defined below) enforce the structure at CI time.

```php
class ProductFilterAttribute extends AbstractBlock {

    protected function render( $attributes, $content, $block ) {
        $show_counts = $attributes['showCounts'] ?? false;

        /** @var SelectableItemsContext $context */
        $context = [
            // Items include 'count' only when $show_counts is true
            'items'          => $this->transform_to_selectable_items( $filter_items, $show_counts ),
            'selectionMode'  => 'multiple',
            'storeNamespace' => 'woocommerce/product-filters',
            'groupLabel'     => $attributes['label'] ?? '',
        ];

        $block->context['woocommerceSelectableItems'] = $context;

        return sprintf(
            '<div %s>%s</div>',
            get_block_wrapper_attributes( [
                'data-wp-interactive' => 'woocommerce/product-filters',
            ] ),
            $content
        );
    }
}
```

#### PHPStan Type (for static analysis)

Add to a dedicated types file (e.g. `phpstan.neon` or a project-level config). Do **not** add type aliases to `phpstan-baseline.neon` — the baseline is reserved for suppressing existing errors:

```neon
parameters:
  typeAliases:
    SelectableItem: '''
      array{
        id: string,
        label: string,
        value: string,
        ariaLabel?: string,
        selected?: bool,
        disabled?: bool,
        type?: string
      }
    '''
    FilterSelectableItem: '''
      array{
        id: string,
        label: string,
        value: string,
        ariaLabel?: string,
        selected?: bool,
        disabled?: bool,
        type?: string,
        count: int,
        termId?: int,
        parent?: int,
        depth?: int,
        menuOrder?: int
      }
    '''
    SelectableItemsContext: '''
      array{
        items: list<SelectableItem>,
        selectionMode: 'single'|'multiple',
        storeNamespace: string,
        groupLabel?: string,
        isLoading?: bool
      }
    '''
```

---

## Implementation Guide

### Implementing as Inner Block (Consumer)

Inner blocks consume the protocol. They render items using a `data-wp-each` template plus a PHP `foreach` SSR fallback, and reuse the parent's store via `storeNamespace` from context for selection bindings.

block.json:

```json
{
  "name": "woocommerce/product-filter-checkbox-list",
  "usesContext": ["woocommerceSelectableItems"],
  "supports": {
    "interactivity": true
  }
}
```

**frontend.ts** — Inner blocks need no frontend JS for selection. Selection action (`actions.toggle`) and live `context.item.selected` binding are provided by the parent store.

#### Inner Block Own Store

Inner blocks own presentational state (show-more toggle, per-item visibility, rendering variants). The parent store never learns about inner-block UI concerns. Two patterns are supported depending on how much control the inner block needs over items:

**A. Direct (recommended default)** — iterate the parent's `state.selectableItems` via a nested `data-wp-interactive="<parent-ns>"` on the items region. `context.item` is set under the parent namespace, so `actions.toggle` and `context.item.selected` resolve directly. Presentational bindings use cross-namespace `::` back to the inner store. Simplest to reason about; reactive out of the box. Used by `checkbox-list` and `chips`.

**B. Mirror** — inner store exposes its own `state.items` that wraps `store(storeNamespace).state.selectableItems` (mapping in extra fields like `hidden`) and its own `actions.toggle` that forwards to the parent. The whole template stays under the inner namespace — no nested scope switching. Choose this when the inner block needs to derive per-iteration data that doesn't belong to the parent contract (custom grouping, pre-sorting, enriched item shapes), or when binding values must resolve without cross-namespace syntax. Cross-store reactivity still works because signals chain through `store(ns).state.x`, but the extra indirection is the trade-off.

Both patterns share the same outer wrapper and protocol plumbing. The walkthrough below uses pattern **A** (direct):

- The **outer wrapper** runs in the inner namespace and carries `data-wp-context` with `storeNamespace` + `displayLimit` so the inner store can read them at runtime.
- The **items region** nests a `data-wp-interactive="<parent-ns>"` so `data-wp-each--item="state.selectableItems"`, `data-wp-on--change="actions.toggle"`, and `data-wp-bind--checked="context.item.selected"` all resolve in the parent store (no cross-namespace syntax inside the iteration, `context.item` is set under the parent namespace which is what `state.selectableItems` and `actions.toggle` expect).
- **Presentational bindings** that belong to the inner block use cross-namespace syntax back to the inner store: `data-wp-bind--hidden="<own-ns>::state.itemHidden"`, `data-wp-bind--style="<own-ns>::state.ratingStyle"`.
- The **show-more button** lives outside the items region — under the inner namespace — and binds `data-wp-on--click="actions.showAll"` + `data-wp-bind--hidden="context.isExpanded"` directly.

The inner store derives per-item fields from the iteration `context.item` provided by `data-wp-each`. Since that context is in the parent namespace, the inner getter uses dynamic `getContext(storeNamespace)` — no hardcoded parent reference, keeping the inner block reusable against any parent store.

If pattern **B** (mirror) is chosen, the items region stays under the inner namespace (no nested `data-wp-interactive`), template iterates `data-wp-each--item="state.items"` (inner store), bindings read `context.item.selected` / `context.item.hidden` (fields added by the inner mirror), and the inner store defines `actions.toggle` that forwards to `store(storeNamespace).actions.toggle(item)`. Parent contract `actions.toggle` can accept an optional `item` argument when it expects to be invoked via a mirror. Other sections below stay the same.

```html
<div
  data-wp-interactive="woocommerce/product-filter-checkbox-list"
  data-wp-context='{"storeNamespace":"woocommerce/product-filters","displayLimit":15}'
>
  <fieldset>
    <!-- Items region: nested into the parent namespace so wp-each + actions resolve to the parent. -->
    <div data-wp-interactive="woocommerce/product-filters">
      <template data-wp-each--item="state.selectableItems" data-wp-each-key="context.item.id">
        <div data-wp-bind--hidden="woocommerce/product-filter-checkbox-list::state.itemHidden">
          <input
            data-wp-bind--checked="context.item.selected"
            data-wp-on--change="actions.toggle"
          >
          <span data-wp-text="context.item.label"></span>
        </div>
      </template>
      <!-- foreach SSR fallback below: first N items only, with data-wp-each-child + per-item data-wp-context including `index`. -->
    </div>
    <button
      data-wp-on--click="actions.showAll"
      data-wp-bind--hidden="context.isExpanded"
    >
      Show more
    </button>
  </fieldset>
</div>
```

```typescript
// frontend.ts
import { store, getContext } from '@wordpress/interactivity';
import type { SelectableItem } from '../../../../types/type-defs/selectable-items';

type ItemWithIndex = SelectableItem & { index: number };

type CheckboxListContext = {
    storeNamespace: string;
    displayLimit: number;
    isExpanded: boolean;
};

type ParentItemContext = {
    item?: ItemWithIndex;
};

const { state } = store( 'woocommerce/product-filter-checkbox-list', {
    state: {
        get itemHidden(): boolean {
            const { isExpanded, storeNamespace, displayLimit } =
                getContext< CheckboxListContext >();
            if ( isExpanded ) return false;
            // Cross-namespace context read: pulls the wp-each iteration
            // `context.item` (stored under the parent ns) without
            // hardcoding the parent namespace.
            const parentCtx =
                getContext< ParentItemContext >( storeNamespace );
            if ( ! parentCtx.item ) return false;
            return parentCtx.item.index >= displayLimit;
        },
    },
    actions: {
        showAll() {
            const context = getContext< CheckboxListContext >();
            context.isExpanded = true;
        },
    },
}, { lock: true } );
```

**Why nested namespaces:** `data-wp-each` stores `context.item` under the namespace active at the `<template>` element. Parent's `actions.toggle` and `context.item.selected` resolve under the parent namespace, so the items region must switch to it. Presentational bindings (`itemHidden`, `ratingStyle`) live in the inner store and use cross-namespace `::` syntax to read iteration context through `getContext(storeNamespace)`.

**Why protocol-specific getter names (`selectableItems` / `removableItems`):** multiple protocols frequently share the same store namespace (e.g. `woocommerce/product-filters` hosts both the selectable-items store and the active-filters removable-items store). A generic `state.items` name collides across protocols and silently overrides. Protocol-aligned names (`selectableItems`, `removableItems`) make both live on the same store without interference.

**PHP Renderer** — Template for `data-wp-each` plus `foreach` for SSR of the first `displayLimit` items. Each SSR item carries its `index` in `data-wp-context` so the inner store's `itemHidden` getter can decide visibility on hydration.

```php
protected function render( $attributes, $content, $block ) {
    if ( empty( $block->context['woocommerceSelectableItems'] ) ) {
        return '';
    }

    $block_context   = $block->context['woocommerceSelectableItems'];
    $items           = $block_context['items'] ?? array();
    $store_namespace = $block_context['storeNamespace'] ?? 'woocommerce/product-filters';
    $display_limit   = 15;
    $visible_items   = array_slice( $items, 0, $display_limit, true );
    $has_more_items  = count( $items ) > $display_limit;

    $wrapper_attributes = array(
        'data-wp-interactive' => 'woocommerce/product-filter-checkbox-list',
        'data-wp-context'     => wp_json_encode( array(
            'storeNamespace' => $store_namespace,
            'displayLimit'   => $display_limit,
        ) ),
    );

    ob_start();
    ?>
    <div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); ?>>
        <fieldset>
            <div data-wp-interactive="<?php echo esc_attr( $store_namespace ); ?>">
                <template data-wp-each--item="state.selectableItems" data-wp-each-key="context.item.id">
                    <div data-wp-bind--hidden="woocommerce/product-filter-checkbox-list::state.itemHidden">
                        <input
                            type="checkbox"
                            data-wp-bind--id="context.item.id"
                            data-wp-bind--value="context.item.value"
                            data-wp-bind--checked="context.item.selected"
                            data-wp-on--change="actions.toggle"
                        >
                        <span data-wp-text="context.item.label"></span>
                    </div>
                </template>
                <?php foreach ( $visible_items as $index => $item ) :
                    $context_item = array_merge( $item, array( 'index' => $index ) );
                    ?>
                    <div
                        data-wp-each-child
                        <?php echo wp_interactivity_data_wp_context( array( 'item' => $context_item ) ); ?>
                        data-wp-bind--hidden="woocommerce/product-filter-checkbox-list::state.itemHidden"
                    >
                        <input
                            type="checkbox"
                            id="<?php echo esc_attr( $item['id'] ); ?>"
                            value="<?php echo esc_attr( $item['value'] ); ?>"
                            <?php checked( ! empty( $item['selected'] ) ); ?>
                            data-wp-bind--checked="context.item.selected"
                            data-wp-on--change="actions.toggle"
                        >
                        <span><?php echo esc_html( $item['label'] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ( $has_more_items ) : ?>
                <button
                    data-wp-on--click="actions.showAll"
                    data-wp-bind--hidden="context.isExpanded"
                >
                    Show more
                </button>
            <?php endif; ?>
        </fieldset>
    </div>
    <?php
    return ob_get_clean();
}
```

Key points:

- **`data-wp-each` template + `foreach` SSR fallback for first `displayLimit` items** — the template renders the rest client-side, so the initial HTML stays small while the full list is still available post-hydration
- **Per-item `data-wp-context` on SSR items includes `index`** — the inner store's `state.itemHidden` reads it via `getContext(storeNamespace).item.index` to decide visibility; hydration then attaches live bindings (`checked`, `hidden`, `toggle`) to the exact DOM wp-each reconciles
- **Nested `data-wp-interactive`** — outer wrapper under the inner namespace, items region switches to the parent namespace so wp-each + parent selection bindings resolve there; presentational bindings (`itemHidden`, `ratingStyle`) use cross-namespace `::` back to the inner store
- **`filterType` discriminator** — inner block can branch rendering (e.g. stars for `'rating'`) without leaking presentation into the parent store

Reference implementation: `ProductFilterCheckboxList.php`, `ProductFilterChips.php`, `checkbox-list/frontend.ts`, `chips/frontend.ts`

### Implementing as Parent Block (Provider)

Parent blocks provide the protocol context to their inner blocks.

#### Filter Example (ProductFilterAttribute.php)

```php
class ProductFilterAttribute extends AbstractBlock {

    protected function render($attributes, $content, $block) {
        // ... existing filter logic to get items ...
        $show_counts = $attributes['showCounts'] ?? false;

        // Transform filter items to standardized context
        $selectable_context = [
            'items'          => $this->transform_to_selectable_items($filter_items, $attribute_name, $show_counts),
            'selectionMode'  => 'multiple',
            'storeNamespace' => 'woocommerce/product-filters',
            'groupLabel'     => $attribute_label,
        ];

        // Provide context to inner blocks
        $block->context['woocommerceSelectableItems'] = $selectable_context;

        // Render inner blocks
        return sprintf(
            '<div %s>%s</div>',
            get_block_wrapper_attributes([
                'data-wp-interactive' => 'woocommerce/product-filters',
            ]),
            $content
        );
    }
}
```

---

## Protocol: Removable Items

Context key: `woocommerceRemovableItems`

Used for lists of items that can be removed individually (active filter chips) with a "clear all" control.

### Context Shape

```typescript
export interface RemovableItem {
  type: string;   // domain discriminator (e.g. "attribute/color", "price")
  value: string;
  label: string;  // display text
}

export interface RemovableItemsContext {
  items: RemovableItem[];   // SSR snapshot — parent's state.removableItems is SSOT post-hydration
  storeNamespace: string;
}
```

### Parent Store Requirements

```typescript
export interface RemovableItemsParentStore {
  state: {
    removableItems: readonly RemovableItem[];   // derived from parent's SSOT; reactive
  };
  actions: {
    remove: () => void;                          // remove current getContext().item
    removeAll: () => void;                       // clear all items
  };
}
```

The getter is `removableItems` (not `items`) for the same reason `selectableItems` is protocol-scoped — multiple protocols (removable-items + selectable-items) routinely live on the same store namespace (e.g. `woocommerce/product-filters`).

Parents assert: `myStore satisfies RemovableItemsParentStore;`

### Rendering Pattern

Inner block (`removable-chips`):

- Wrap in `data-wp-interactive="<storeNamespace>"`
- Iterate `state.removableItems` via `data-wp-each` for reactive rendering (items can be added/removed dynamically)
- SSR fallback: `foreach` over `context.items` with per-item `data-wp-context` and `data-wp-each-child`
- Per-item binding: `data-wp-on--click="actions.remove"`, label via `data-wp-text="context.item.label"`
- Clear-all button: `data-wp-on--click="actions.removeAll"`

Reference implementation: `ProductFilterRemovableChips.php`, `ProductFilterClearButton.php`, `inner-blocks/active-filters/frontend.ts`.

---

## Protocol: Range Input

Context key: `woocommerceRangeInput`

Used for two-ended numeric range controls (price slider, generic range).

### Context Shape

```typescript
export interface RangeInputContext {
  min: number;
  max: number;
  currentMin: number;
  currentMax: number;
  step?: number;
  storeNamespace: string;
  isLoading?: boolean;
}
```

### Parent Store Requirements

```typescript
export interface RangeInputParentStore {
  actions: {
    setMin: ( event: Event ) => void;
    setMax: ( event: Event ) => void;
  };
}
```

Generic names (`setMin`/`setMax`) — not price-specific — so the protocol can host non-price range inputs in the future. Parents assert: `myStore satisfies RangeInputParentStore;`

### Rendering Pattern

Inner block (`price-slider`):

- Wrap in `data-wp-interactive="<storeNamespace>"`
- Two `<input type="range">`, one per bound
- Min input: `data-wp-on--input="actions.setMin"`, `data-wp-bind--value="state.<minGetter>"` (parent decides getter — e.g. `state.minPrice`)
- Max input: `data-wp-on--input="actions.setMax"`, analogous for max
- Parent owns display formatting (currency, locale) via its own state getters

Reference implementation: `ProductFilterPriceSlider.php`, `inner-blocks/price-filter/frontend.ts`, `inner-blocks/price-slider/frontend.ts`.
