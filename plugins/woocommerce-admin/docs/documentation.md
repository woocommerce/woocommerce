# How To Document Components

We rely on inline documentation and a markdown example file for both [the docs site on github.io,](https://woocommerce.github.io/woocommerce-admin/#/) and the DevDocs section in wp-admin. This allows us to keep the docs site up-to-date if any components change, because the documentation is kept in the same place as the code. Read on for how to build out the docs files and devdocs section.

## 1. Add the inline documentation to generate a markdown file

This consists of a docblock for a component description at the top of the component, correct `PropTypes` definitions for all props used, and docblocks for each prop in PropTypes. See [count/index.js](https://github.com/woocommerce/woocommerce-admin/blob/master/packages/components/src/count/index.js) for a simple example, or [table/table.js](https://github.com/woocommerce/woocommerce-admin/blob/master/packages/components/src/table/table.js) for a very detailed example.

## 2. Generate the docs with `npm run docs`

This creates the file in `/docs/components/` for your new component, or adds it to the existing component doc (if this is a sub-component, like TablePlaceholder).

You can test this by running `npx docsify serve docs`, it will spin up a server at localhost:3000 to preview the docs. This also live-reloads as you're making changes.

## 3. Add an `example.md` file

You can use [`card/example.md`](https://raw.githubusercontent.com/woocommerce/woocommerce-admin/master/packages/components/src/card/example.md) as a simple example, or [`pagination/example.md`](https://raw.githubusercontent.com/woocommerce/woocommerce-admin/master/packages/components/src/pagination/example.md) as an example with state ([using `withState` HoC from gutenberg](https://github.com/WordPress/gutenberg/tree/master/packages/compose/src/with-state)).

## 4. Add your example to `client/devdocs/examples.json`

Keep these alphabetized. Optional properties here are `render`, `filePath`, and `docPath`. `render` defaults to `My{ComponentName}`, and `filePath` defaults to `/docs/component/packages/{component-name-as-slug}`. `docPath` designates the origin of the component and efaults to `packages` for components from `/packages/components`.

Now you can visit `/wp-admin/admin.php?page=wc-admin&path=/devdocs` to see your component in action.
