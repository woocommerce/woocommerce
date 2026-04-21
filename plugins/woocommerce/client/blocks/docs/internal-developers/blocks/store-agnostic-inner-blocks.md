# Store-Agnostic Inner Blocks Pattern

## Overview

This document defines the **`woocommerce/selectableItems` context protocol** - a contract between parent blocks and reusable inner blocks for rendering selectable item lists (swatches, chips, dropdowns, etc.).

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

The context object that parents MUST provide.

### Core Fields (Required)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `items` | `SelectableItem[]` | **Yes** | Items to render |
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
| `showCounts` | `boolean` | `false` | Show product counts next to items (filters) |

## SelectableItem

Each item in the `items` array MUST have:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `label` | `string \| ReactNode` | **Yes** | Display text (ReactNode for custom rendering) |
| `value` | `string` | **Yes** | Value for selection/submission |
| `selected` | `boolean` | No | Current selection state (default: false) |
| `disabled` | `boolean` | No | Whether item can be selected (default: false) |
| `ariaLabel` | `string` | Conditional | **Required** if `label` is ReactNode |

Each item MAY have:

| Field | Type | Description |
|-------|------|-------------|
| `count` | `number` | Product count (for filters) |
| `color` | `string` | Hex color for swatches (e.g., "#FF0000") |
| `image` | `string` | Image URL for swatches |
| `type` | `string` | Type discriminator (e.g., "attribute/color") |
| `id` | `number` | Term/attribute ID |
| `parent` | `number` | Parent term ID (hierarchical) |
| `depth` | `number` | Nesting depth (hierarchical) |
| `menuOrder` | `number` | Sort order |

Items are extensible — extensions can add arbitrary fields (e.g. `badge`, `tooltip`, `rating`) and consume them in custom inner blocks. Built-in inner blocks ignore unknown fields.

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
3. Show counts when `showCounts === true` and `item.count` exists
4. Render color swatch when `item.color` exists
5. Render image swatch when `item.image` exists (fallback if no color)
6. Render text-only when neither `color` nor `image` exists
7. Apply disabled styling when `item.disabled === true`
8. Use `groupLabel` for fieldset legend (screen reader accessible)

---

# Design Rationale

## Bundled Presentation Data

Presentation data (color, image) is bundled directly on items, not in a separate map:

- One data structure to build and consume
- Direct property access: `item.color` instead of `presentation[item.value].color`
- Aligns with existing `FilterOptionItem` pattern
- Optional fields are simply null/undefined when not applicable

## Backward Compatibility

`SelectableItem` extends the existing `FilterOptionItem` shape:

**Current `FilterOptionItem`** (from `product-filters/types.ts`):
```typescript
type FilterOptionItem = (
  | { label: string; ariaLabel?: string; }
  | { label: ReactNode; ariaLabel: string; }
) & {
  value: string;
  selected?: boolean;
  count: number;
  id?: number;
  parent?: number;
  depth?: number;
  menuOrder?: number;
};
```

**`SelectableItem` additions:**
- `count` becomes optional (variation selector doesn't need it)
- `disabled` added (for unavailable variations)
- `color` added (for swatches)
- `image` added (for swatches)
- `type` added (type discriminator)

---

# Type Definitions

This section provides copy-paste-ready type definitions for both TypeScript and PHP. These definitions enforce the protocol specification above.

## TypeScript

Location: `assets/js/types/type-defs/selectable-items.ts`

```typescript
import type { ReactNode } from 'react';

/**
 * Context protocol for selectable item lists.
 * 
 * @see docs/internal-developers/blocks/store-agnostic-inner-blocks.md
 */
export interface SelectableItemsContext {
  /** Items to render */
  items: SelectableItem[];
  
  /** Selection behavior */
  selectionMode: 'single' | 'multiple';
  
  /** Action name the inner block should call on selection */
  selectAction: string;
  
  /** Parent's Interactivity API store namespace */
  storeNamespace: string;
  
  /** Screen reader label for the group (rendered as fieldset legend) */
  groupLabel?: string;
  
  /** Show product counts next to items (filters) */
  showCounts?: boolean;
}

/**
 * Selectable item - extends FilterOptionItem with presentation fields.
 */
export type SelectableItem = (
  | { label: string; ariaLabel?: string; }
  | { label: ReactNode; ariaLabel: string; }
) & {
  /** Value for selection/submission */
  value: string;
  
  /** Current selection state */
  selected?: boolean;
  
  /** Product count (filters) */
  count?: number;
  
  /** Whether item can be selected */
  disabled?: boolean;
  
  /** Type discriminator (e.g., 'attribute/color') */
  type?: string;
  
  /** Swatch color (hex, e.g., "#FF0000") */
  color?: string;
  
  /** Swatch image URL */
  image?: string;
  
  /** Term/attribute ID */
  id?: number;
  
  /** Parent term ID for nested taxonomies */
  parent?: number;
  
  /** Nesting depth for hierarchical display */
  depth?: number;
  
  /** Menu order for sorting */
  menuOrder?: number;

  /** Extensions can add arbitrary display fields */
  [key: string]: unknown;
};
```

## PHP

No base class or trait needed — parent blocks set `$block->context` directly. PHPStan type aliases (defined below) enforce the structure at CI time.

```php
class ProductFilterAttribute extends AbstractBlock {

    protected function render( $attributes, $content, $block ) {
        /** @var SelectableItemsContext $context */
        $context = [
            'items'          => $this->transform_to_selectable_items( $filter_items ),
            'selectionMode'  => 'multiple',
            'selectAction'   => 'toggleFilter',
            'storeNamespace' => 'woocommerce/product-filters',
            'groupLabel'     => $attributes['label'] ?? '',
            'showCounts'     => $attributes['showCounts'] ?? true,
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
        label: string,
        value: string,
        selected?: bool,
        disabled?: bool,
        count?: int,
        color?: string,
        image?: string,
        type?: string,
        id?: int,
        parent?: int,
        depth?: int,
        menuOrder?: int,
        ariaLabel?: string
      }
    '''
    SelectableItemsContext: '''
      array{
        items: list<SelectableItem>,
        selectionMode: 'single'|'multiple',
        selectAction: string,
        storeNamespace: string,
        groupLabel?: string,
        showCounts?: bool
      }
    '''
```

---

# Examples

These examples show valid context objects that conform to the protocol.

## Color Filter (multi-select with swatches)

```php
$context = [
    'items' => [
        [
            'label'    => 'Red',
            'value'    => 'red',
            'selected' => false,
            'count'    => 5,
            'color'    => '#FF0000',
        ],
        [
            'label'    => 'Blue',
            'value'    => 'blue',
            'selected' => true,
            'count'    => 3,
            'color'    => '#0000FF',
            'image'    => 'blue-pattern.jpg',  // Can have both color and image
        ],
    ],
    'selectionMode'  => 'multiple',
    'selectAction'   => 'toggleFilter',
    'storeNamespace' => 'woocommerce/product-filters',
    'groupLabel'     => 'Filter by Color',  // Screen reader: "Filter by Color"
    'showCounts'     => true,
];
```

## Size Filter (multi-select, text-only)

```php
$context = [
    'items' => [
        ['label' => 'Small',  'value' => 'small',  'selected' => false, 'count' => 8],
        ['label' => 'Medium', 'value' => 'medium', 'selected' => true,  'count' => 12],
        ['label' => 'Large',  'value' => 'large',  'selected' => false, 'count' => 6],
        // No color/image - renders as text chips
    ],
    'selectionMode'  => 'multiple',
    'selectAction'   => 'toggleFilter',
    'storeNamespace' => 'woocommerce/product-filters',
    'groupLabel'     => 'Filter by Size',
    'showCounts'     => true,
];
```

## Variation Selector (single-select with disabled)

```php
$context = [
    'items' => [
        ['label' => 'Red',   'value' => 'red',   'selected' => true,  'color' => '#FF0000'],
        ['label' => 'Blue',  'value' => 'blue',  'selected' => false, 'color' => '#0000FF'],
        ['label' => 'Green', 'value' => 'green', 'selected' => false, 'color' => '#00FF00', 'disabled' => true],
    ],
    'selectionMode'  => 'single',
    'selectAction'   => 'setAttribute',
    'storeNamespace' => 'woocommerce/add-to-cart-with-options',
    'groupLabel'     => 'Select Color',
    // No showCounts needed for variation selector
];
```

## Hierarchical Taxonomy (nested categories)

```php
$context = [
    'items' => [
        ['label' => 'Clothing', 'value' => 'clothing', 'id' => 10, 'parent' => 0, 'depth' => 0, 'count' => 50],
        ['label' => 'T-Shirts', 'value' => 't-shirts', 'id' => 11, 'parent' => 10, 'depth' => 1, 'count' => 20],
        ['label' => 'Hoodies',  'value' => 'hoodies',  'id' => 12, 'parent' => 10, 'depth' => 1, 'count' => 15],
    ],
    'selectionMode'  => 'multiple',
    'selectAction'   => 'toggleFilter',
    'storeNamespace' => 'woocommerce/product-filters',
    'groupLabel'     => 'Filter by Category',
    'showCounts'     => true,
];
```

## Rating Filter (ReactNode label)

```php
// PHP renders the stars SVG as label
$context = [
    'items' => [
        [
            'label'     => '<svg>★★★★★</svg>',  // Rendered as HTML
            'ariaLabel' => '5 stars',            // Required when label is not plain text
            'value'     => '5',
            'selected'  => false,
            'count'     => 12,
            'type'      => 'rating',
        ],
        // ...
    ],
    'selectionMode'  => 'multiple',
    'selectAction'   => 'toggleFilter',
    'storeNamespace' => 'woocommerce/product-filters',
    'groupLabel'     => 'Filter by Rating',
    'showCounts'     => true,
];
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
    $show_counts     = $block_context['showCounts'] ?? false;
    $store_namespace = $block_context['storeNamespace'] ?? 'woocommerce/product-filters';
    $select_action   = $block_context['selectAction'] ?? 'toggleFilter';

    // Pre-compute id and ariaLabel, re-index with array_values
    // to guarantee JSON array (not object) for data-wp-each.
    $context_items = array_values(
        array_map(
            function ( $item ) use ( $show_counts ) {
                $item['id']        = $item['type'] . '-' . $item['value'];
                $item['ariaLabel'] = $this->get_aria_label( $item, $show_counts );
                return $item;
            },
            $items
        )
    );

    // Items go into Interactivity API context for data-wp-each.
    $wrapper_attributes = array(
        'data-wp-interactive' => $store_namespace,
        'data-wp-context'     => wp_json_encode(
            array(
                'items'      => $context_items,
                'showCounts' => $show_counts,
            ),
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
                            data-wp-bind--checked="state.isFilterSelected"
                        >
                        <span data-wp-text="context.item.label"></span>
                        <span data-wp-bind--hidden="!context.showCounts">
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
                            data-wp-bind--checked="state.isFilterSelected"
                        >
                        <span><?php echo esc_html( $item['label'] ); ?></span>
                        <?php if ( $show_counts ) : ?>
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
- **`state.isFilterSelected`** and other state getters come from the parent store via namespace delegation

Reference implementation: `ProductFilterCheckboxList.php`, `ProductFilterChips.php`

## Implementing as Parent Block (Provider)

Parent blocks provide the protocol context to their inner blocks.

### Filter Example (ProductFilterAttribute.php)
```php
class ProductFilterAttribute extends AbstractBlock {
    
    protected function render($attributes, $content, $block) {
        // ... existing filter logic to get items ...
        
        // Transform filter items to standardized context
        $selectable_context = [
            'items'          => $this->transform_to_selectable_items($filter_items, $attribute_name),
            'selectionMode'  => 'multiple',
            'selectAction'   => 'toggleFilter',
            'storeNamespace' => 'woocommerce/product-filters',
            'groupLabel'     => $attribute_label,
            'showCounts'     => $attributes['showCounts'] ?? true,
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
    
    private function transform_to_selectable_items(array $filter_items, string $attribute_name): array {
        // Get presentation data once, keyed by slug
        $presentation = $this->get_presentation_data($attribute_name);
        
        return array_map(function($item) use ($presentation) {
            $result = [
                'label'    => $item['label'],
                'value'    => $item['value'],
                'selected' => $item['selected'] ?? false,
                'count'    => $item['count'] ?? 0,
                'type'     => $item['type'] ?? null,
            ];
            
            // Bundle presentation data directly on item
            if (isset($presentation[$item['value']])) {
                $result['color'] = $presentation[$item['value']]['color'] ?? null;
                $result['image'] = $presentation[$item['value']]['image'] ?? null;
            }
            
            return $result;
        }, $filter_items);
    }
    
    /**
     * Get visual presentation data from term meta.
     */
    private function get_presentation_data(string $attribute_name): array {
        $presentation = [];
        $terms = get_terms(['taxonomy' => 'pa_' . $attribute_name, 'hide_empty' => false]);
        
        foreach ($terms as $term) {
            $color = get_term_meta($term->term_id, 'color', true);
            $image = get_term_meta($term->term_id, 'image', true);
            
            if ($color || $image) {
                $presentation[$term->slug] = [
                    'color' => $color ?: null,
                    'image' => $image ?: null,
                ];
            }
        }
        
        return $presentation;
    }
}
```

### Variation Selector Example (VariationSelectorAttribute.php)
```php
class VariationSelectorAttribute extends AbstractBlock {
    
    protected function render($attributes, $content, $block) {
        // ... existing variation logic to get terms ...
        
        // Transform attribute terms to standardized context
        $selectable_context = [
            'items'          => $this->transform_to_selectable_items($attribute_terms, $attribute_name),
            'selectionMode'  => 'single',
            'selectAction'   => 'setAttribute',
            'storeNamespace' => 'woocommerce/add-to-cart-with-options',
            'groupLabel'     => $attribute_label,
            // No showCounts needed for variation selector
        ];
        
        // Provide context to inner blocks
        $block->context['woocommerce/selectableItems'] = $selectable_context;
        
        return sprintf(
            '<div %s>%s</div>',
            get_block_wrapper_attributes([
                'data-wp-interactive' => 'woocommerce/add-to-cart-with-options',
            ]),
            $content
        );
    }
    
    private function transform_to_selectable_items(array $terms, string $attribute_name): array {
        // Get presentation data once
        $presentation = $this->get_presentation_data($attribute_name);
        
        return array_map(function($term) use ($presentation) {
            $result = [
                'label'    => $term['label'],
                'value'    => $term['value'],
                'selected' => $term['isSelected'] ?? false,
                'disabled' => $term['isDisabled'] ?? false,
            ];
            
            // Bundle presentation data directly on item
            if (isset($presentation[$term['value']])) {
                $result['color'] = $presentation[$term['value']]['color'] ?? null;
                $result['image'] = $presentation[$term['value']]['image'] ?? null;
            }
            
            return $result;
        }, $terms);
    }
    
    /**
     * Get visual presentation data from term meta.
     * Same source as filters - ensures visual consistency.
     */
    private function get_presentation_data(string $attribute_name): array {
        $presentation = [];
        $terms = get_terms(['taxonomy' => 'pa_' . $attribute_name, 'hide_empty' => false]);
        
        foreach ($terms as $term) {
            $color = get_term_meta($term->term_id, 'color', true);
            $image = get_term_meta($term->term_id, 'image', true);
            
            if ($color || $image) {
                $presentation[$term->slug] = [
                    'color' => $color ?: null,
                    'image' => $image ?: null,
                ];
            }
        }
        
        return $presentation;
    }
}
```

---

# Display Styles

The protocol supports multiple display styles as separate inner blocks, all consuming the same context:

| Block Name | Renders As | Use Case |
|------------|-----------|----------|
| `woocommerce/selectable-chips` | Button chips | Compact, multi-select |
| `woocommerce/selectable-pills` | Radio/checkbox pills | Traditional form style |
| `woocommerce/selectable-swatches` | Color boxes / images | Visual attributes |
| `woocommerce/selectable-dropdown` | Select element | Space-constrained |
| `woocommerce/selectable-list` | Checkbox list | Detailed with counts |

Each variant:
1. Reads the `woocommerce/selectableItems` context in `render()`
2. Renders items with its own HTML/styling
3. Works in any parent that provides the protocol context

## Swatch Rendering Example

Presentation data is bundled on items - direct property access:

**Rendering a single swatch item:**
```php
private function render_item(array $item, string $selection_mode, bool $show_counts): string {
    $has_color = !empty($item['color']);
    $has_image = !empty($item['image']);
    
    $swatch_content = '';
    
    if ($has_color) {
        $swatch_content = sprintf(
            '<span class="wc-block-swatch__color" style="background-color: %s;" aria-hidden="true"></span>',
            esc_attr($item['color'])
        );
    } elseif ($has_image) {
        $swatch_content = sprintf(
            '<img class="wc-block-swatch__image" src="%s" alt="" aria-hidden="true" />',
            esc_url($item['image'])
        );
    } else {
        // No visual data - render as text chip fallback
        $swatch_content = sprintf(
            '<span class="wc-block-swatch__text">%s</span>',
            esc_html($item['label'])
        );
    }
    
    $input_type = $selection_mode === 'single' ? 'radio' : 'checkbox';
    $label = is_string($item['label']) ? $item['label'] : ($item['ariaLabel'] ?? '');
    
    return sprintf(
        '<label class="wc-block-swatch %s %s">
            <input 
                type="%s" 
                value="%s"
                data-wp-bind--checked="state.isSelected"
                data-wp-bind--disabled="state.isDisabled"
                data-wp-on--change="actions.handleSelect"
                data-wp-context=\'%s\'
            />
            %s
            <span class="wc-block-swatch__label">%s</span>
            %s
        </label>',
        $item['selected'] ? 'is-selected' : '',
        $item['disabled'] ? 'is-disabled' : '',
        $input_type,
        esc_attr($item['value']),
        wp_json_encode(['item' => $item]),
        $swatch_content,
        esc_html($label),
        $show_counts && isset($item['count']) ? '<span class="wc-block-swatch__count">(' . $item['count'] . ')</span>' : ''
    );
}
```

---

# Migration Path

### Phase 1: Create New Blocks
1. Create `SelectableSwatches` block implementing the protocol
2. Test inside both filter and variation selector parents

### Phase 2: Refactor Existing Blocks
1. Update `ProductFilterChips` to read from the protocol context
2. Update `ProductFilterCheckboxList` similarly
3. Update `VariationSelectorAttributeOptions` to provide standardized context
4. Deprecate direct store references in inner blocks

### Phase 3: Unify
1. Both filter and variation selector use same inner block set
2. `displayStyle` attribute in both parents selects which inner block renders
3. Single source of truth for selectable item UI

---

# Benefits

1. **True Reusability**: Same inner block works in any parent providing the protocol
2. **Consistent UX**: Swatches look/behave identically across features
3. **Easier Maintenance**: Fix once, works everywhere
4. **Extensibility**: New display styles are just new inner blocks consuming the protocol
5. **Type Safety**: Protocol defined in both TypeScript and PHP catches mismatches at build time

---

# Resolved Questions

1. **Swatch data source**: Presentation data (color/image) is **bundled directly on items**. Parent blocks fetch term meta and merge during `transform_to_selectable_items()`. Direct access: `$item['color']`.

2. **Item type alignment**: `SelectableItem` extends `FilterOptionItem` with `color`, `image`, and `disabled`. Backward compatible.

3. **Bundled vs separate presentation**: Bundled is simpler - one data structure, direct property access. Size items just don't have `color`/`image` set.

4. **Protocol enforcement**: Use this document (D) as source of truth, with TypeScript types (B) and PHP interface (C) for build-time enforcement in each language.
