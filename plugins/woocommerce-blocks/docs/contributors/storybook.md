# Storybook

This repo includes [Storybook](https://storybook.js.org) tooling so we can test and develop components in isolation.

The storybook is automatically built and published to [GitHub pages](https://woocommerce.github.io/woocommerce-gutenberg-products-block/) on every push to the main branch. See [travis.yml](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/main/.travis.yml) for details.

https://woocommerce.github.io/woocommerce-gutenberg-products-block/

## How to run Storybook locally and test components

- `npm run storybook`
- Point your browser at port 6006, e.g. http://localhost:6006
- Play with components 🎛!

## How to add a story for a component

- Add a `stories` folder alongside the component.
- Add stories in `.js` files in this folder. 

If you're stuck, copy source of an existing story to get started.
