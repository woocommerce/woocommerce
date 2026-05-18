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
- **Inner store owns presentation state** — built-in list UIs mirror parent items into their own store before rendering. Parent owns selection data; child owns show-more, local index, swatch/rating display, etc.
- **SSR fallback via `data-wp-each-child`** — PHP renders initially visible items once with per-item `data-wp-context`. Hydration reconciles those nodes against the inner store's mirrored `state.items`.
- **No parent knowledge of children** — parent provides protocol data only. Children may read optional extra item fields, but missing extras must degrade safely.

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

### Required context

Parents provide `woocommerceSelectableItems` to inner blocks.

```typescript
export interface SelectableItemsContext< T = unknown > {
	items: SelectableItem< T >[];
	selectionMode: 'single' | 'multiple';
	storeNamespace: string;
	groupLabel?: string;
	isLoading?: boolean;
	filterType?: string;
}
```

Rules:

- `items`: SSR/editor snapshot. Parent store remains live source after hydration.
- `selectionMode`: child chooses checkbox/radio semantics.
- `storeNamespace`: parent Interactivity API store namespace.
- `groupLabel`: accessible fieldset label.
- `isLoading`: child may render skeletons.
- `filterType`: optional parent/domain discriminator. Child must fallback when unknown.

### Required item shape

```typescript
export type SelectableItem< T = unknown > = (
	| { label: string; ariaLabel?: string }
	| { label: ReactNode; ariaLabel: string }
) & {
	id: string;
	value: string;
	selected?: boolean;
	disabled?: boolean;
	type?: string;
} & T;
```

Base stays clean: identity, label, value, selection state. Extra data lives in `T`.

### Required parent store

Store named by `storeNamespace` MUST expose:

```typescript
export interface SelectableItemsParentStore< T = unknown > {
	state: {
		selectableItems: readonly SelectableItem< T >[];
	};
	actions: {
		toggle: ( item?: SelectableItem< T > ) => void;
	};
}
```

Rules:

- `state.selectableItems`: live items, with `selected` derived from parent SSOT.
- `actions.toggle( item? )`: toggles parent SSOT. If `item` omitted, parent may read `context.item`.
- Parents assert `satisfies SelectableItemsParentStore< T >`.
- Parent does **not** add child-owned fields like `index`.

### Optional consumer extensions

Parents may add extra item fields. Children may use fields they understand. Contract:

- Extra fields optional, always.
- Missing extra field must not break rendering.
- Parent must not shape data for a specific child.
- Child-only metadata must be derived by child.

Product filters currently provide:

```typescript
export type FilterItemFields = {
	count?: number;
	termId?: number;
	parent?: number;
	depth?: number;
	menuOrder?: number;
	attributeQueryType?: 'and' | 'or';
	color?: string;
};
```

### Known built-in extensions

| Consumer | Optional fields read | Fallback |
| --- | --- | --- |
| `checkbox-list` | `count`, `color`, `depth`, `filterType === 'rating'` | Text label, no count, no swatch, no indent |
| `chips` | `count`, `color` | Text label, no count, no swatch style |

Both built-ins mirror parent items and add local `index` for show-more. Parent never provides `index`.

### SSR / hydration gotchas

- PHP renders first `displayLimit` items plus selected overflow items.
- Template iterates child `state.items`, not parent `state.selectableItems`.
- Child `state.items` mirrors `store(storeNamespace).state.selectableItems` and adds local `index`.
- Child `actions.toggle()` forwards current item to parent `actions.toggle( item )`.
- `data-wp-each-child` SSR nodes must carry `data-wp-context={ item }` under child namespace.
- Hydration owns the full list; hidden overflow comes from child `state.itemHidden`.

### Minimal child store pattern

```typescript
const { state } = store( 'woocommerce/product-filter-chips', {
	state: {
		get items() {
			const { storeNamespace } = getContext< { storeNamespace: string } >();
			return store< SelectableItemsParentStore >( storeNamespace )
				.state.selectableItems.map( ( item, index ) => ( {
					...item,
					index,
				} ) );
		},
		get itemHidden() {
			const { item } = getContext< { item?: SelectableItem< { index?: number } > } >();
			const { displayLimit, isExpanded } = getContext< { displayLimit: number; isExpanded: boolean } >();
			return ! isExpanded && ! item?.selected && ( item?.index ?? 0 ) >= displayLimit;
		},
	},
	actions: {
		toggle() {
			const { storeNamespace, item } = getContext< {
				storeNamespace: string;
				item?: SelectableItem;
			} >();
			store< SelectableItemsParentStore >( storeNamespace ).actions.toggle( item );
		},
	},
} );
```

### Minimal renderer pattern

```php
<div data-wp-interactive="woocommerce/product-filter-chips" data-wp-context='{"storeNamespace":"woocommerce/product-filters","displayLimit":15,"isExpanded":false}'>
	<div class="wc-block-product-filter-chips__items">
		<?php foreach ( $visible_items as $item ) : ?>
			<button data-wp-each-child <?php echo wp_interactivity_data_wp_context( array( 'item' => $item ) ); ?> data-wp-on--click="actions.toggle">
				<?php echo esc_html( $item['label'] ); ?>
			</button>
		<?php endforeach; ?>
		<template data-wp-each--item="state.items" data-wp-each-key="context.item.id">
			<button data-wp-on--click="actions.toggle" data-wp-text="context.item.label"></button>
		</template>
	</div>
</div>
```

Reference implementation: `ProductFilterCheckboxList.php`, `ProductFilterChips.php`, `checkbox-list/frontend.ts`, `chips/frontend.ts`.

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
