# Components

These are shared components used by the blocks. If there's a component that is more universally useful, it should go into [`@woocommerce/components`](https://github.com/woocommerce/woocommerce-admin/tree/master/packages/components)– these components are specific to the Gutenberg context.

The `*-control` components here are designed to exist in the `InspectorControls` sidebar, or in a Placeholder component for the "edit state" of a block.

## `GridContentControl`

A combination of toggle controls for content visibility in product grids.

## `GridLayoutControl`

A combination of range controls for product grid layout settings.

## `ProductOrderbyControl`

A pre-configured SelectControl for product orderby settings.

## `ProductPreview`

Display a preview for a given product.

## `ProductAttributeTermControl`

A component using [`SearchListControl`](https://woocommerce.github.io/woocommerce-admin/#/components/packages/search-list-control) to show product attributes as selectable options. Only allows for selecting attribute terms from one attribute at a time (multiple terms can be selected).

## `ProductCategoryControl`

A component using [`SearchListControl`](https://woocommerce.github.io/woocommerce-admin/#/components/packages/search-list-control) to show product categories as selectable options. Options are displayed in hierarchy. Can select multiple categories.

## `ProductControl`

A component using [`SearchListControl`](https://woocommerce.github.io/woocommerce-admin/#/components/packages/search-list-control) to show products as selectable options. Only one product can be selected at a time.

## `ProductsControl`

A component using [`SearchListControl`](https://woocommerce.github.io/woocommerce-admin/#/components/packages/search-list-control) to show products as selectable options. Multiple products can be selected at once.

## Icons

These are a collection of custom icons used by the blocks or components, usually from Material.

## Utilities

There are some functions that work across components, these have been extracted into this utilities folder.

## Block Title

A block that is responsible for showing the title for some of our blocks.
