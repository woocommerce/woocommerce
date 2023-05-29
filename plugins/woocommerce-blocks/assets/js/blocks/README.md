# Blocks

Our blocks are generally made up of up to 4 files:

```text
|- block.js
|- editor.scss
|- index.js
|- style.scss
```

The only required file is `index.js`, this sets up the block using [`registerBlockType`](https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/block-registration/). Each block has edit and save functions.

The scss files are split so that things in `style` are added to the editor _and_ frontend, while styles in `editor` are only added to the editor. Most of our blocks should use core components that won't need CSS though.

## Editing

A simple edit function can live in `index.js`, but most blocks are a little more complicated, so the edit function instead returns a Block component, which lives in `block.js`. By using a component, we can use React lifecycle methods to fetch data or save state.

The [Newest Products block](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/5c9d587fcc0b9e652813a42b66eafa5520c7ac88/assets/js/blocks/product-new/block.tsx) is a good example to read over, this is a simple block that fetches the products and renders them using the ProductPreview component.

We include settings in the sidebar, called the Inspector in gutenberg. [See an example of this.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/5c9d587fcc0b9e652813a42b66eafa5520c7ac88/assets/js/blocks/product-new/block.tsx#L71)

Other blocks have the concept of an "edit state", like when you need to pick a product in the Featured Product block, or [pick a category in the Products by Category block.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/5c9d587fcc0b9e652813a42b66eafa5520c7ac88/assets/js/blocks/product-category/block.js#L140)
