# Product element blocks

Product element blocks display individual pieces of product content, such as the image, title, price, rating, and add to cart button. Product layouts, including All Products and Single Product, use these blocks to compose their product displays.

## Structure

Each element has its own directory with block metadata and the code needed for editing, saving, and rendering it. Shared editor code lives in `shared/`. The root `index.js` imports the element registration files, while `component-init.js` registers components that are loaded when needed. `frontend.ts` contains the shared product element callback for interactive updates.

The `utils/` directory contains block registration and rendering helpers. Import elements through `@woocommerce/product-element-blocks` and these helpers through `@woocommerce/product-element-utils`.

## Adding an element

Place the block in its own directory, define its metadata in `block.json`, and register it with `registerProductBlockType` when it needs the product block registration behavior. Add its entry to the root `index.js`; if it has a component loaded when needed, register that in `component-init.js` too. Keep existing block names and asset handles stable because saved content and extensions can depend on them.
