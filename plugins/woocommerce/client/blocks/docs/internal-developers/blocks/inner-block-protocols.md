# Inner Block Protocols

## Overview

This document defines the **context protocol pattern** used by WooCommerce blocks to let reusable inner blocks work with any parent store. Three concrete protocols exist today:

| Context Key | Purpose | Inner Blocks |
|-------------|---------|--------------|
| `woocommerce/selectableItems` | Select/deselect items (filters, variations) | checkbox-list, chips |
| `woocommerce/removableItems` | Remove individual items (active filters) | removable-chips |
| `woocommerce/rangeInput` | Numeric range input (price, slider) | price-slider |

Each protocol follows the same pattern — only item shape and action names differ.

## Problem Statement

WooCommerce blocks need reusable UI components (chips, swatches, pills, sliders) that can work inside multiple parent blocks with different Interactivity API stores. Without a protocol, inner blocks get tightly coupled to a single store namespace, preventing true reusability.

## Solution: Context Protocol Pattern

Inner blocks become **presentational** — they read a standardized context protocol and call parent-provided actions instead of directly referencing a specific store.

```
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

- **Context key** namespaced under `woocommerce/*` (e.g. `woocommerce/selectableItems`)
- **`storeNamespace`** field on every context object — tells inner block which parent store to resolve `actions.*` / `state.*` against
- **Fixed action & getter names** (not configurable via context fields) — inner blocks hardcode them
- **TS contract interface** (`*ParentStore`) — parents assert conformance via `satisfies`
- **Items-carrying contexts** (`selectableItems`, `removableItems`) set per-item `data-wp-context` on each rendered row so `actions.*` and `state.*` getters can read `getContext().item` universally — both dynamic (`data-wp-each`) and static (`foreach`) modes

## Enforcement via TypeScript `satisfies`

Every protocol ships a `*ParentStore` interface. Parent stores assert:

```typescript
import type { SelectableItemsParentStore } from '../../types/type-defs/selectable-items';
// or: RemovableItemsParentStore, RangeInputParentStore

myStore satisfies SelectableItemsParentStore;
```

Missing method/getter → compile error. No runtime cost.

---

# Protocol: Selectable Items

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

## Parent Store Requirements

The store registered under `storeNamespace` MUST expose:

| Name | Kind | Contract |
|------|------|----------|
| `state.isSelected` | getter | Returns `boolean` for the current `getContext().item` derived from parent's domain state (active filters, chosen variation, etc.). |
| `actions.toggle` | action | Toggles selection for the current `getContext().item`. No args — reads item from context. Mutates parent's SSOT (e.g. `activeFilters`). |

Fixed names (not configurable). Inner blocks bind `data-wp-bind--checked="state.isSelected"` and call `actions.toggle`. Item iteration uses `context.items` (raw, PHP-rendered).

Enforcement via TypeScript contract:

```typescript
import type { SelectableItemsParentStore } from '../../types/type-defs/selectable-items';

const myStore = {
	state: { get items() { /* ... */ }, /* ... */ },
	actions: { toggle: () => { /* ... */ }, /* ... */ },
};

// Compile-time check — TS error if `state.isSelected` or `actions.toggle` is missing/wrong-shaped.
myStore satisfies SelectableItemsParentStore;
```

## Selection State Model

- **SSOT** lives in parent's domain state (e.g. `context.activeFilters` for filters).
- **`context.items`** holds the raw input list (PHP-rendered into context). Inner blocks iterate this.
- **`item.selected`** on raw items is only a PHP SSR hint (matches SSOT at render time). Never the JS binding source.
- **`state.isSelected`** getter reads `getContext().item` + SSOT → returns boolean. Reactive — SSOT changes propagate automatically to all bindings.
- **`actions.toggle`** mutates SSOT only. Never touches raw `item.selected`.

External mutations (active-filter removal, cross-block sync) flow through automatically: mutate `activeFilters` → every `state.isSelected` binding re-evaluates → checkboxes update across all blocks. Works for both dynamic (`data-wp-each`) and static (`foreach`-rendered) item lists.

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

Inner blocks consume the protocol. For items rendering they reuse the parent's store via `storeNamespace` from context and render items using `data-wp-each`.

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

**frontend.ts** — Inner blocks need no frontend JS for selection. Selection action (`actions.toggle`) and selection getter (`state.isSelected`) are provided by the parent store.

### Optional: Inner Block Own Store

Inner blocks MAY register their own Interactivity store for **block-local UI state** (show-more toggle, local focus, animation flags, etc.) that the parent doesn't need to know about.

Pattern validated:

1. Outer wrapper uses the inner block's own namespace via `data-wp-interactive="<own-ns>"`.
2. The region that renders items (where `data-wp-each` lives) switches back to the parent namespace via a nested `data-wp-interactive="<parent-ns>"` — so `state.isSelected` and `actions.toggle` resolve against the parent store.
3. Block-local directives (`data-wp-on--click="actions.myLocalAction"`, `data-wp-bind--hidden="state.myLocalState"`) outside the items region resolve against the inner block's own store.

```html
<div data-wp-interactive="woocommerce/product-filter-checkbox-list">
  <!-- own-namespace region: block-local UI -->
  <button data-wp-on--click="actions.toggleShowMore">Show more</button>

  <!-- switch to parent namespace for items + selection -->
  <div data-wp-interactive="woocommerce/product-filters">
    <template data-wp-each--item="context.items" ...>
      <input
        data-wp-on--change="actions.toggle"
        data-wp-bind--checked="state.isSelected"
      >
    </template>
  </div>
</div>
```

```typescript
// frontend.ts
import { store } from '@wordpress/interactivity';

store( 'woocommerce/product-filter-checkbox-list', {
  state: { /* block-local */ },
  actions: { /* block-local */ },
}, { lock: true } );
```

**Why nesting is required inside the items region:** `data-wp-context` is namespace-scoped. `data-wp-each` populates `context.item` under the current namespace. Parent's `actions.toggle` and `state.isSelected` getter call `getContext()` without a namespace argument — which defaults to the action's own store namespace. If items were rendered under the inner block's namespace, parent action/getter would read an empty context and fail. Cross-namespace references via `namespace::` syntax don't fix this because `getContext()` inside the parent action still defaults to the parent namespace.

**When to add own store:** only if there's genuinely block-local state. Otherwise skip `frontend.ts` entirely.

**PHP Renderer** — Uses `data-wp-each` template with `data-wp-each-child` SSR fallback.
```php
protected function render( $attributes, $content, $block ) {
    if ( empty( $block->context['woocommerce/selectableItems'] ) ) {
        return '';
    }

    $block_context   = $block->context['woocommerce/selectableItems'];
    $items           = $block_context['items'] ?? array();
    $store_namespace = $block_context['storeNamespace'] ?? 'woocommerce/product-filters';

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
                            data-wp-on--change="actions.toggle"
                            data-wp-bind--value="context.item.value"
                            data-wp-bind--checked="state.isSelected"
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
                            data-wp-on--change="actions.toggle"
                            value="<?php echo esc_attr( $item['value'] ); ?>"
                            data-wp-bind--checked="state.isSelected"
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
- **`data-wp-each--item`** iterates `context.items` (raw PHP-rendered list) and sets `context.item` per iteration
- **`data-wp-each-child`** items provide server-side HTML before JS hydration; use raw `context.items` from PHP for SSR fallback
- **`array_values()`** is required — input arrays may have non-sequential keys (e.g. taxonomy term IDs), which causes `json_encode` to produce a JSON object instead of array
- **`data-wp-text`** for labels (not `data-wp-html` — that directive does not exist in the Interactivity API)
- **`state.isSelected`** reflects current selection reactively — parent's getter reads `context.item` + SSOT on each evaluation

### Static Items Mode (`dynamicItems: false`)

When parent sets `dynamicItems: false`, inner blocks skip the `data-wp-each` template and `data-wp-each-child` attributes. Items are rendered with plain PHP `foreach`. **Per-item `data-wp-context` is still emitted** so `state.isSelected` (which reads `getContext().item`) remains reactive:

```php
$dynamic_items = $block_context['dynamicItems'] ?? true;

// Only render client-side template if dynamic
if ( $dynamic_items ) {
    // <template data-wp-each--item="context.items">...</template>
}

foreach ( $context_items as $item ) { ?>
    <div
        class="..."
        <?php if ( $dynamic_items ) : ?>
            data-wp-each-child
        <?php endif; ?>
        <?php echo wp_interactivity_data_wp_context( array( 'item' => $item ) ); ?>
    >
        <!-- render item, bind `state.isSelected` for selection -->
    </div>
<?php }
```

Use `dynamicItems: false` when:
- Items are a small fixed set (rating stars, stock statuses)
- No show-more functionality needed
- Labels contain HTML (SVG, icons) that `data-wp-text` can't render

Why per-item context is unconditional: `actions.toggle` and `state.isSelected` both call `getContext().item`. Without `data-wp-context` on each item wrapper, clicks fire against an undefined item. Set it whether dynamic or static.

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

---

# Protocol: Removable Items

Context key: `woocommerce/removableItems`

Used for lists of items that can be removed individually (active filter chips) with a "clear all" control.

## Context Shape

```typescript
export interface RemovableItem {
  type: string;   // domain discriminator (e.g. "attribute/color", "price")
  value: string;
  label: string;  // display text
}

export interface RemovableItemsContext {
  items: RemovableItem[];   // SSR snapshot — parent's state.items is SSOT post-hydration
  storeNamespace: string;
}
```

## Parent Store Requirements

```typescript
export interface RemovableItemsParentStore {
  state: {
    items: readonly RemovableItem[];   // derived from parent's SSOT; reactive
  };
  actions: {
    remove: () => void;                // remove current getContext().item
    removeAll: () => void;             // clear all items
  };
}
```

Parents assert: `myStore satisfies RemovableItemsParentStore;`

## Rendering Pattern

Inner block (`removable-chips`):
- Wrap in `data-wp-interactive="<storeNamespace>"`
- Iterate `state.items` via `data-wp-each` for reactive rendering
- SSR fallback: `foreach` over `context.items` with per-item `data-wp-context`
- Per-item binding: `data-wp-on--click="actions.remove"`, label via `data-wp-text="context.item.label"`
- Clear-all button: `data-wp-on--click="actions.removeAll"`

Reference implementation: `ProductFilterRemovableChips.php`, `ProductFilterClearButton.php`, `inner-blocks/active-filters/frontend.ts`.

---

# Protocol: Range Input

Context key: `woocommerce/rangeInput`

Used for two-ended numeric range controls (price slider, generic range).

## Context Shape

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

## Parent Store Requirements

```typescript
export interface RangeInputParentStore {
  actions: {
    setMin: ( event: Event ) => void;
    setMax: ( event: Event ) => void;
  };
}
```

Generic names (`setMin`/`setMax`) — not price-specific — so the protocol can host non-price range inputs in the future. Parents assert: `myStore satisfies RangeInputParentStore;`

## Rendering Pattern

Inner block (`price-slider`):
- Wrap in `data-wp-interactive="<storeNamespace>"`
- Two `<input type="range">`, one per bound
- Min input: `data-wp-on--input="actions.setMin"`, `data-wp-bind--value="state.<minGetter>"` (parent decides getter — e.g. `state.minPrice`)
- Max input: `data-wp-on--input="actions.setMax"`, analogous for max
- Parent owns display formatting (currency, locale) via its own state getters

Reference implementation: `ProductFilterPriceSlider.php`, `inner-blocks/price-filter/frontend.ts`, `inner-blocks/price-slider/frontend.ts`.
