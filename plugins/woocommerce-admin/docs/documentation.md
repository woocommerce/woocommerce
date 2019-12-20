# How To Document Components

We rely on a markdown documentation file for [the docs site on github.io,](https://woocommerce.github.io/woocommerce-admin/#/) and an example JavaScript file for the DevDocs section in wp-admin. Read on for how to build out the docs files and devdocs section.

## 1. Create a markdown documentation file

Use `npm run docs` to generate a `README.md` for your new component. This will create a basic documentation file containing the component name, description comment, component props, and a placeholder section for example usage.

Edit the documentation file for completeness. We recommend using a simple component as a reference (like the [Card component](https://raw.githubusercontent.com/woocommerce/woocommerce-admin/master/packages/components/src/card/README.md)).

## 2. Create a JavaScript example file

The JavaScript example will be rendered in the DevDocs section and serves as interactive documentation for the component. Create a `docs/example.js` file in the component directory.

See [count/docs/example.js](https://github.com/woocommerce/woocommerce-admin/blob/master/packages/components/src/count/docs/example.js) for a simple example, or [table/docs/example.js](https://github.com/woocommerce/woocommerce-admin/blob/master/packages/components/src/table/docs/example.js) for a more detailed example.

## 3. Preview the documentation site

You can test the documentation site by running `npx docsify serve docs`. It will spin up a server at localhost:3000 to preview the docs. This also live-reloads as you're making changes.

## 4. Add your example to `client/devdocs/examples.json`

Keep these alphabetized. The `component` property is required. You can optionally provide a `filePath`, but it will default to `/docs/component/packages/{component-name-as-slug}`.

Now you can visit `/wp-admin/admin.php?page=wc-admin&path=/devdocs` to see your component in action.
