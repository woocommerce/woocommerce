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

Parent blocks provide this context key via `providesContext`. Inner blocks consume it via `usesContext`.

### Parent block.json (provider)

```json
{
  "name": "woocommerce/product-filter-attribute",
  "providesContext": {
    "woocommerce/selectableItems": "selectableItems"
  }
}
```

The `providesContext` maps the context key `woocommerce/selectableItems` to the block attribute `selectableItems`. In practice, the PHP `render()` method builds the context object and passes it when rendering inner blocks:

```php
( new \WP_Block( $parsed_block, array(
    'woocommerce/selectableItems' => $selectable_context,
) ) )->render();
```

### Inner block.json (consumer)

```json
{
  "name": "woocommerce/selectable-swatches",
  "usesContext": ["woocommerce/selectableItems"],
  "ancestor": [
    "woocommerce/product-filter-attribute",
    "woocommerce/add-to-cart-with-options-variation-selector-attribute"
  ]
}
```

The inner block declares which parents it can be nested inside via `ancestor`, and receives the protocol data through `$block->context['woocommerce/selectableItems']` in PHP.

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

Location: `assets/js/blocks/product-filters/types.ts` (or shared types file)

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
};
```

## PHP

Location: `src/Blocks/BlockTypes/SelectableItemsContextInterface.php`

```php
<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Interface for blocks that provide SelectableItemsContext.
 * 
 * @see docs/internal-developers/blocks/store-agnostic-inner-blocks.md
 */
interface SelectableItemsContextInterface {
    /**
     * Build the selectable items context for inner blocks.
     *
     * @return array{
     *   items: array<array{
     *     label: string,
     *     value: string,
     *     selected?: bool,
     *     disabled?: bool,
     *     count?: int,
     *     color?: string,
     *     image?: string,
     *     type?: string,
     *     id?: int,
     *     parent?: int,
     *     depth?: int,
     *     menuOrder?: int,
     *     ariaLabel?: string
     *   }>,
     *   selectionMode: 'single'|'multiple',
     *   selectAction: string,
     *   storeNamespace: string,
     *   groupLabel?: string,
     *   showCounts?: bool
     * }
     */
    public function get_selectable_items_context(): array;
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

Inner blocks consume the protocol. They read context and delegate actions to the parent.

**block.json**
```json
{
  "name": "woocommerce/selectable-items",
  "title": "Selectable Items",
  "category": "woocommerce",
  "ancestor": [
    "woocommerce/product-filter-attribute",
    "woocommerce/product-filter-taxonomy",
    "woocommerce/product-filter-status",
    "woocommerce/add-to-cart-with-options-variation-selector-attribute"
  ],
  "usesContext": ["woocommerce/selectableItems"],
  "supports": {
    "interactivity": true
  }
}
```

**frontend.ts**
```typescript
import { store, getContext } from '@wordpress/interactivity';

interface ItemContext {
  item: SelectableItem;
}

// Inner block registers minimal store - just for local UI state
store('woocommerce/selectable-items', {
  state: {
    get isSelected() {
      const ctx = getContext<ItemContext>();
      return ctx.item.selected;
    },
    get isDisabled() {
      const ctx = getContext<ItemContext>();
      return ctx.item.disabled ?? false;
    },
  },
  actions: {
    /**
     * Called on item click/change.
     * Delegates to parent's action via context.
     */
    handleSelect() {
      const ctx = getContext<ItemContext>();
      const parentCtx = getContext<SelectableItemsContext>('parent');
      
      // Call the parent's mapped action
      // Parent provides 'selectAction' which points to their store action
      const { actions } = store(parentCtx.storeNamespace);
      actions[parentCtx.selectAction]?.(ctx.item);
    },
  },
});
```

**PHP Renderer**
```php
class SelectableSwatches extends AbstractBlock {
    
    protected function render($attributes, $content, $block) {
        $context = $block->context['woocommerce/selectableItems'] ?? null;
        if (!$context || empty($context['items'])) {
            return '';
        }
        
        $items = $context['items'];
        $selection_mode = $context['selectionMode'] ?? 'multiple';
        $show_counts = $context['showCounts'] ?? false;
        $input_type = $selection_mode === 'single' ? 'radio' : 'checkbox';
        
        ob_start();
        ?>
        <div 
            <?php echo get_block_wrapper_attributes(); ?>
            data-wp-interactive="woocommerce/selectable-items"
        >
            <fieldset role="<?php echo $selection_mode === 'single' ? 'radiogroup' : 'group'; ?>">
                <?php if (!empty($context['groupLabel'])): ?>
                    <legend class="screen-reader-text">
                        <?php echo esc_html($context['groupLabel']); ?>
                    </legend>
                <?php endif; ?>
                
                <?php foreach ($items as $item): ?>
                    <label
                        class="wc-block-swatch <?php echo $item['selected'] ? 'is-selected' : ''; ?>"
                        data-wp-context='<?php echo wp_json_encode(['item' => $item]); ?>'
                    >
                        <input 
                            type="<?php echo $input_type; ?>"
                            value="<?php echo esc_attr($item['value']); ?>"
                            data-wp-bind--checked="state.isSelected"
                            data-wp-on--change="actions.handleSelect"
                        />
                        <?php if (!empty($item['color'])): ?>
                            <span class="wc-block-swatch__color" style="background-color: <?php echo esc_attr($item['color']); ?>"></span>
                        <?php endif; ?>
                        <span class="wc-block-swatch__label"><?php echo esc_html($item['label']); ?></span>
                        <?php if ($show_counts && isset($item['count'])): ?>
                            <span class="wc-block-swatch__count">(<?php echo $item['count']; ?>)</span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        </div>
        <?php
        return ob_get_clean();
    }
}
```

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

# Open Questions

1. **Editor experience**: How do we handle the editor-side (React) equivalent of this pattern? Block context works in editor, but Interactivity API doesn't run there.

2. **Store namespace exposure**: Is it safe for inner blocks to know parent's store namespace? Alternative: abstract event/callback system.

---

# Resolved Questions

1. **Swatch data source**: Presentation data (color/image) is **bundled directly on items**. Parent blocks fetch term meta and merge during `transform_to_selectable_items()`. Direct access: `$item['color']`.

2. **Item type alignment**: `SelectableItem` extends `FilterOptionItem` with `color`, `image`, and `disabled`. Backward compatible.

3. **Bundled vs separate presentation**: Bundled is simpler - one data structure, direct property access. Size items just don't have `color`/`image` set.

4. **Protocol enforcement**: Use this document (D) as source of truth, with TypeScript types (B) and PHP interface (C) for build-time enforcement in each language.
