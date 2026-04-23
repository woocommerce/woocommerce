# Selectable Items Inner Blocks

## Overview

This document defines the **`woocommerce/selectableItems` context protocol** — a contract between parent blocks and reusable inner blocks for rendering selectable item lists (chips, checkbox lists, dropdowns, etc.).

**Status:** Draft specification
**Protocol Name:** `woocommerce/selectableItems`
**Version:** 1.0

## Problem Statement

WooCommerce blocks need reusable UI components (chips, swatches, pills) that can work inside multiple parent blocks with different Interactivity API stores:

| Parent Block | Store Namespace | Selection Model |
|--------------|-----------------|-----------------|
| Product Filter Attribute | `woocommerce/product-filters` | Multi-select |
| Variation Selector | `woocommerce/add-to-cart-with-options` | Single-select |

Current inner blocks are tightly coupled to a single store namespace, preventing true reusability.

## Solution: Context Protocol Pattern

Inner blocks become **presentational** - they read a standardized context protocol and call parent-provided callbacks instead of directly referencing a store.

```
┌─────────────────────────────────────────────────────────┐
│  D: Protocol Specification (this document)              │
│  └── Defines the contract both sides must follow        │
├─────────────────────────────────────────────────────────┤
│  Parent Block (implements protocol)                     │
│  ├── Registers own Interactivity store                  │
│  ├── Provides context matching protocol shape           │
│  ├── Maps store actions to protocol callbacks           │
│  └── Handles business logic (filtering, variation)      │
├─────────────────────────────────────────────────────────┤
│  Reusable Inner Block (consumes protocol)               │
│  ├── Reads context per protocol specification           │
│  ├── Renders items based on context data                │
│  ├── Calls protocol-defined callbacks on interaction    │
│  └── Zero knowledge of parent's store/business logic    │
└─────────────────────────────────────────────────────────┘
```

---

# Protocol Specification

## Context Key

```
woocommerce/selectableItems
```

Items are dynamic (computed at render time from database queries), so parent blocks do **not** use `providesContext` in block.json. Instead, they pass context directly when rendering inner blocks:

```php
// Parent block render():
( new \WP_Block( $parsed_block, array(
    'woocommerce/selectableItems' => $context,
) ) )->render();
```

In the editor, parent blocks use `BlockContextProvider` to pass the same data:

```jsx
<BlockContextProvider value={ { 'woocommerce/selectableItems': context } }>
    { children }
</BlockContextProvider>
```

### Inner block.json (consumer)

Inner blocks declare the context key they consume via `usesContext`, and which parents they can be nested inside via `ancestor`:

```json
{
  "name": "woocommerce/product-filter-checkbox-list",
  "usesContext": ["woocommerce/selectableItems"],
  "ancestor": [
    "woocommerce/product-filter-attribute",
    "woocommerce/product-filter-status",
    "woocommerce/product-filter-taxonomy",
    "woocommerce/product-filter-rating"
  ]
}
```

Inner blocks receive the protocol data through `$block->context['woocommerce/selectableItems']` in PHP.

## SelectableItemsContext

The context object that parents MUST provide. Typed as `SelectableItemsContext<T>` where `T` is the extra fields the parent adds to each item (default: `unknown`).

### Core Fields (Required)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `items` | `SelectableItem<T>[]` | **Yes** | Items to render |
| `selectionMode` | `'single' \| 'multiple'` | **Yes** | Selection behavior |
| `selectAction` | `string` | **Yes** | Action name to call on selection |
| `storeNamespace` | `string` | **Yes** | Parent's Interactivity API store |

### Accessibility Fields (Optional)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `groupLabel` | `string` | No | Screen reader label for the group. Rendered as `<legend>` in fieldset. Example: "Filter by Color" |

### Presentation Fields (Optional)

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `dynamicItems` | `boolean` | `true` | Use `data-wp-each` for dynamic item rendering. Set to `false` for static item lists (rating, stock status) that don't need show-more and may contain HTML labels. |
| `isLoading` | `boolean` | `false` | Parent is fetching items. Inner blocks show skeleton/loading state. |

## SelectableItem

`SelectableItem<T = unknown>` — base fields plus an optional generic extension `T` for domain-specific data.

Each item in the `items` array MUST have:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | `string` | **Yes** | Unique identifier used as `data-wp-each-key`. Format: `"{type}-{value}"` e.g. `"attribute-red"` |
| `label` | `string \| ReactNode` | **Yes** | Display text (ReactNode for custom rendering) |
| `value` | `string` | **Yes** | Value for selection/submission |
| `ariaLabel` | `string` | Conditional | **Required** if `label` is ReactNode |
| `selected` | `boolean` | No | Current selection state (default: false) |
| `disabled` | `boolean` | No | Whether item can be selected (default: false) |
| `hidden` | `boolean` | No | Whether item is hidden (default: false) |
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

## Callback Contract

When user selects an item, inner block MUST:

1. Get `storeNamespace` and `selectAction` from context
2. Call `store(storeNamespace).actions[selectAction](item)`
3. Pass the full `SelectableItem` object to the action

Parent's action handler receives the item and handles business logic.

## Rendering Rules

Inner blocks SHOULD:

1. Render `<input type="radio">` when `selectionMode === 'single'`
2. Render `<input type="checkbox">` when `selectionMode === 'multiple'`
3. Apply disabled styling when `item.disabled === true`
4. Skip (hide) items when `item.hidden === true`
5. Use `groupLabel` for fieldset legend (screen reader accessible)
6. Show skeleton/loading UI when `isLoading === true`

Inner blocks typed against `FilterItemFields` MAY additionally:

7. Show counts when `item.count` exists

---

# Design Rationale

## Generic Extension Pattern

`SelectableItem<T>` uses a generic parameter instead of a flat union of optional fields:

- Base fields are shared by all consumers (id, label, value, selected, disabled, hidden, type)
- Domain-specific fields live in `T` — typed, not untyped `[key: string]: unknown`
- Filter blocks use `FilterOptionItem = SelectableItem<FilterItemFields>` with count, parent, depth, etc.
- A variation selector would use `SelectableItem<{ price?: string; stockStatus?: string }>` etc.
- TypeScript enforces correct shape at each call site with no extra runtime cost

## Backward Compatibility

`SelectableItem<T>` replaces the old flat `FilterOptionItem`. Key changes:

| Old `FilterOptionItem` | New `SelectableItem<FilterItemFields>` |
|------------------------|----------------------------------------|
| `id?: number` (optional, number) | `id: string` (required, string — used as `data-wp-each-key`) |
| `count: number` (required) | `count: number` in `FilterItemFields` (required for filters, absent for other consumers) |
| No `disabled` | `disabled?: boolean` on base type |
| No `hidden` | `hidden?: boolean` on base type |
| No `type` | `type?: string` on base type |

---

# Type Definitions

This section provides copy-paste-ready type definitions for both TypeScript and PHP. These definitions enforce the protocol specification above.

## TypeScript

Location: `assets/js/types/type-defs/selectable-items.ts`

```typescript
import type { ReactNode } from 'react';

export type SelectableItem< T = unknown > = (
	| { label: string; ariaLabel?: string }
	| { label: ReactNode; ariaLabel: string }
) & {
	/** Unique key for data-wp-each. Format: "{type}-{value}" */
	id: string;
	value: string;
	selected?: boolean;
	disabled?: boolean;
	hidden?: boolean;
	type?: string;
} & T;

export interface SelectableItemsContext< T = unknown > {
	items: SelectableItem< T >[];
	selectionMode: 'single' | 'multiple';
	selectAction: string;
	storeNamespace: string;
	groupLabel?: string;
	dynamicItems?: boolean;
	isLoading?: boolean;
}

export type SelectableItemsBlockContext< T = unknown > = {
	'woocommerce/selectableItems': SelectableItemsContext< T >;
};
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

## PHP

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
            'selectAction'   => 'toggleFilter',
            'storeNamespace' => 'woocommerce/product-filters',
            'groupLabel'     => $attributes['label'] ?? '',
        ];

        $block->context['woocommerce/selectableItems'] = $context;

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

## PHPStan Type (for static analysis)

Add to `phpstan-baseline.neon` or a types file:

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
        hidden?: bool,
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
        hidden?: bool,
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
        selectAction: string,
        storeNamespace: string,
        groupLabel?: string,
        dynamicItems?: bool,
        isLoading?: bool
      }
    '''
```

---

# Implementation Guide

## Implementing as Inner Block (Consumer)

Inner blocks consume the protocol. They have **no store of their own** — they reuse the parent's store via `storeNamespace` from context and render items using `data-wp-each`.

**block.json**
```json
{
  "name": "woocommerce/product-filter-checkbox-list",
  "usesContext": ["woocommerce/selectableItems"],
  "supports": {
    "interactivity": true
  }
}
```

**frontend.ts** — Inner blocks need no frontend JS. All actions are delegated to the parent store.
```typescript
// No store needed. Actions (toggleFilter, showAll, etc.) are provided
// by the parent store via storeNamespace context.
```

**PHP Renderer** — Uses `data-wp-each` template with `data-wp-each-child` SSR fallback.
```php
protected function render( $attributes, $content, $block ) {
    if ( empty( $block->context['woocommerce/selectableItems'] ) ) {
        return '';
    }

    $block_context   = $block->context['woocommerce/selectableItems'];
    $items           = $block_context['items'] ?? array();
    $store_namespace = $block_context['storeNamespace'] ?? 'woocommerce/product-filters';
    $select_action   = $block_context['selectAction'] ?? 'toggleFilter';

    // Pre-compute id and ariaLabel, re-index with array_values
    // to guarantee JSON array (not object) for data-wp-each.
    $context_items = array_values(
        array_map(
            function ( $item ) {
                $item['id']        = $item['type'] . '-' . $item['value'];
                return $item;
            },
            $items
        )
    );

    // Items go into Interactivity API context for data-wp-each.
    $wrapper_attributes = array(
        'data-wp-interactive' => $store_namespace,
        'data-wp-context'     => wp_json_encode(
            array( 'items' => $context_items ),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        ),
    );

    ob_start();
    ?>
    <div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); ?>>
        <fieldset>
            <div class="my-inner-block__items">
                <!-- Client-side template: data-wp-each iterates context.items -->
                <template
                    data-wp-each--item="context.items"
                    data-wp-each-key="context.item.id"
                >
                    <div class="my-inner-block__item">
                        <input
                            type="checkbox"
                            data-wp-bind--id="context.item.id"
                            data-wp-bind--aria-label="context.item.ariaLabel"
                            data-wp-on--change="actions.<?php echo esc_attr( $select_action ); ?>"
                            data-wp-bind--value="context.item.value"
                            data-wp-bind--checked="context.item.selected"
                        >
                        <span data-wp-text="context.item.label"></span>
                        <span data-wp-bind--hidden="!context.item.count">
                            (<span data-wp-text="context.item.count"></span>)
                        </span>
                    </div>
                </template>
                <!-- SSR fallback: replaced by template clones on hydration -->
                <?php foreach ( $context_items as $item ) { ?>
                    <div class="my-inner-block__item"
                        data-wp-each-child
                        <?php echo wp_interactivity_data_wp_context( array( 'item' => $item ) ); ?>
                    >
                        <input
                            type="checkbox"
                            id="<?php echo esc_attr( $item['id'] ); ?>"
                            aria-label="<?php echo esc_attr( $item['ariaLabel'] ); ?>"
                            data-wp-on--change="actions.<?php echo esc_attr( $select_action ); ?>"
                            value="<?php echo esc_attr( $item['value'] ); ?>"
                            data-wp-bind--checked="context.item.selected"
                        >
                        <span><?php echo esc_html( $item['label'] ); ?></span>
                        <?php if ( isset( $item['count'] ) ) : ?>
                            <span>(<?php echo esc_html( $item['count'] ); ?>)</span>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>
        </fieldset>
    </div>
    <?php
    return ob_get_clean();
}
```

Key points:
- **`data-wp-interactive`** is set to `$store_namespace` (the parent's store), not a block-specific store
- **`data-wp-each--item`** iterates `context.items` and sets `context.item` per iteration
- **`data-wp-each-child`** items provide server-side HTML before JS hydration
- **`array_values()`** is required — input arrays may have non-sequential keys (e.g. taxonomy term IDs), which causes `json_encode` to produce a JSON object instead of array
- **`data-wp-text`** for labels (not `data-wp-html` — that directive does not exist in the Interactivity API)
- **`context.item.selected`** reflects current selection state — parent updates this when selection changes

### Static Items Mode (`dynamicItems: false`)

When parent sets `dynamicItems: false`, inner blocks skip the `data-wp-each` template and `data-wp-each-child` attributes. Items are rendered with plain PHP `foreach` only:

```php
$dynamic_items = $block_context['dynamicItems'] ?? true;

// Only render template if dynamic
if ( $dynamic_items ) {
    // <template data-wp-each--item="context.items">...</template>
}

// Foreach items - conditionally add data-wp-each-child
foreach ( $context_items as $item ) {
    $attrs = $dynamic_items
        ? 'data-wp-each-child ' . wp_interactivity_data_wp_context( array( 'item' => $item ) )
        : '';
    // Render item with $attrs
}
```

Use `dynamicItems: false` when:
- Items are a small fixed set (rating stars, stock statuses)
- No show-more functionality needed
- Labels contain HTML (SVG, icons) that `data-wp-text` can't render

Reference implementation: `ProductFilterCheckboxList.php`, `ProductFilterChips.php`

## Implementing as Parent Block (Provider)

Parent blocks provide the protocol context to their inner blocks.

### Filter Example (ProductFilterAttribute.php)
```php
class ProductFilterAttribute extends AbstractBlock {

    protected function render($attributes, $content, $block) {
        // ... existing filter logic to get items ...
        $show_counts = $attributes['showCounts'] ?? false;

        // Transform filter items to standardized context
        $selectable_context = [
            'items'          => $this->transform_to_selectable_items($filter_items, $attribute_name, $show_counts),
            'selectionMode'  => 'multiple',
            'selectAction'   => 'toggleFilter',
            'storeNamespace' => 'woocommerce/product-filters',
            'groupLabel'     => $attribute_label,
        ];

        // Provide context to inner blocks
        $block->context['woocommerce/selectableItems'] = $selectable_context;

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
