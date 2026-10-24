# Inner Block Protocols

> **Experimental:** These protocols are internal and may change without notice.

WooCommerce reusable inner blocks use small context protocols so they can render UI for different parent blocks without coupling to a specific parent store.

| Context key | Purpose | Inner blocks |
| --- | --- | --- |
| `woocommerce/selectableItems` | Select/deselect items (filters, variation attributes) | checkbox-list, chips, dropdown |
| `woocommerce/removableItems` | Remove individual items (active filters) | removable-chips |
| `woocommerce/rangeInput` | Numeric range input (price, slider) | price-slider |

## Shared pattern

- Parent provides protocol context and owns business state.
- Inner block reads the protocol context, renders UI, and calls fixed parent actions.
- Every context includes `storeNamespace`, which points to the parent Interactivity API store.
- Parent stores expose protocol-scoped getters such as `state.selectableItems` or `state.removableItems`; generic `state.items` is avoided so protocols can coexist.
- Parent stores should assert conformance with the matching `*ParentStore` TypeScript interface via `satisfies`.
- Inner blocks may derive presentation-only data locally. Parent data should not include child-owned fields such as list indexes.
- Server-rendered fallback items use `data-wp-each-child` with per-item `data-wp-context`; hydration reconciles them with the inner store.

## Display styles

A display style block is an inner block that renders a protocol context for a specific parent block. Display style blocks opt in through block support metadata:

```json
{
    "supports": {
        "woocommerce": {
            "innerBlockDisplayStyle": true
        }
    }
}
```

Display style blocks must:

- Declare `supports.woocommerce.innerBlockDisplayStyle` as `true`.
- Declare the protocol context in `usesContext`.
- Declare the supported parent block in `ancestor`.
- Render from protocol fields, not from parent-specific stores.
- Treat extension fields as optional.

The editor uses the support flag for discovery. `usesContext` and `ancestor` are validation signals so unrelated inner blocks do not appear in display style controls.

## Selectable Items

Context key: `woocommerce/selectableItems`

Used by selectable list UIs such as checkbox-list and chips.

Parents pass the context directly when rendering inner blocks because items are computed dynamically:

```php
( new \WP_Block( $parsed_block, array(
    'woocommerce/selectableItems' => $context,
) ) )->render();
```

In the editor, use `BlockContextProvider` with the same key.

### Context

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

- `items` is the SSR/editor snapshot.
- `storeNamespace` points to the live parent store used after hydration.
- `groupLabel` is used for accessible fieldset labels.
- `isLoading` and `filterType` are optional rendering hints.

### Item shape

```typescript
export type SelectableItem< T = unknown > = (
	| { label: string; ariaLabel?: string }
	| { label: ReactNode; ariaLabel: string }
) & {
	id: string;
	value: string;
	selected?: boolean;
	disabled?: boolean;
	hidden?: boolean;
	type?: string;
} & T;
```

`hidden` is optional protocol-level visibility metadata. Extra data belongs in `T`. Children may read optional extra fields, but missing fields must degrade safely.

### Parent store

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

- `state.selectableItems` is the live source after hydration.
- `actions.toggle( item? )` updates the parent source of truth.
- `hidden` may be provided by parents or derived by children to hide an item without removing it from the collection.
- Parent must not add child-owned fields such as `index`.

### Built-in consumers

Product filters currently add these optional fields:

```typescript
export type FilterItemFields = {
	count?: number;
	termId?: number;
	parent?: number;
	depth?: number;
	menuOrder?: number;
	attributeQueryType?: 'and' | 'or';
	visual?: {
		type: 'color' | 'image' | 'none';
		value: string;
	};
};
```

| Consumer | Optional fields read | Fallback |
| --- | --- | --- |
| `checkbox-list` | `count`, `visual`, `depth`, `filterType === 'rating'` | Text label, no count, no swatch, no indent |
| `chips` | `count`, `visual` | Text label, no count, no swatch |

Checkbox-list and chips mirror parent items into child `state.items`, adding local `index` for show-more and setting `hidden` when an item should be hidden. Their templates bind overflow visibility with `context.item.hidden`.

## Removable Items

Context key: `woocommerce/removableItems`

Used by active-filter chips and similar removable item lists.

### Context

```typescript
export interface RemovableItem {
	type: string;
	value: string;
	label: string;
}

export interface RemovableItemsContext {
	items: RemovableItem[];
	storeNamespace: string;
}
```

### Parent store

```typescript
export interface RemovableItemsParentStore {
	state: {
		removableItems: readonly RemovableItem[];
	};
	actions: {
		remove: () => void;
		removeAll: () => void;
	};
}
```

Rendering pattern:

- Inner block iterates `state.removableItems` from the parent store.
- SSR fallback renders `context.items` with `data-wp-each-child` and per-item context.
- Per-item remove calls `actions.remove`; clear-all calls `actions.removeAll`.

Reference implementations: `ProductFilterRemovableChips.php`, `ProductFilterClearButton.php`, `inner-blocks/active-filters/frontend.ts`.

## Range Input

Context key: `woocommerce/rangeInput`

Used by two-ended numeric controls such as price sliders.

### Context

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

### Parent store

```typescript
export interface RangeInputParentStore {
	actions: {
		setMin: ( event: Event ) => void;
		setMax: ( event: Event ) => void;
	};
}
```

Rendering pattern:

- Inner block renders min/max inputs.
- Inputs call parent `actions.setMin` and `actions.setMax`.
- Parent owns formatting, validation, and display state.

Reference implementations: `ProductFilterPriceSlider.php`, `inner-blocks/price-filter/frontend.ts`, `inner-blocks/price-slider/frontend.ts`.
