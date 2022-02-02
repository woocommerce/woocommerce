# Storybook & Components

This repo includes [Storybook](https://storybook.js.org) tooling so we can test and develop components in isolation.

The storybook is automatically built and published to [GitHub pages](https://woocommerce.github.io/woocommerce-gutenberg-products-block/) on every push to the main branch. See [travis.yml](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/.travis.yml) for details.

https://woocommerce.github.io/woocommerce-gutenberg-products-block/

## Where are our components?
We have components in a few folders, for different contexts.

- [`assets/js/base/components`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/assets/js/base/components)
- [`assets/js/editor-components`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/assets/js/editor-components)
- [`assets/js/icons`](https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/assets/js/icons)

__`assets/js/base/components`__ are used in front-end code, as well as editor & admin.
These components help us build consistent interfaces across the front end (shopper) experience and elsewhere.
Because they can be used in the front end and editor, components in this folder should:

-  Perform efficiently - i.e. not adversely affect page performance/experience.
-  Have lean dependencies - i.e. not bloat the payload unnecessarily.
-  Look consistent in common themes; ideally should allow themes to adjust appearance as necessary.

__`assets/js/editor-components`__ are used in the editor UI for our blocks.
They allow us to build a consistent and powerful UI for merchants for authoring content relating to Woo data - e.g. selecting products or product attributes. Because they are focused on the editor, they can rely on known editor dependencies and optimise styling for the editor only.

__`assets/js/icons`__ is a suite of icons and SVG images that we use in our interfaces.

For more info about individual components, refer to [Storybook](https://woocommerce.github.io/woocommerce-gutenberg-products-block/) or individual readme files.

## How to run Storybook locally and test components

- `npm run storybook`
- Point your browser at port 6006, e.g. http://localhost:6006
- Play with components 🎛!

## How to add a story for a component

- Add a `stories` folder alongside the component.
- Add stories in `.js` files in this folder.

If you're stuck, copy source of an existing story to get started.

<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/components.md)
<!-- /FEEDBACK -->

