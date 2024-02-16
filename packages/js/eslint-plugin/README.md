# ESLint Plugin

This is an [ESLint](https://eslint.org/) plugin including configurations and custom rules for WooCommerce development.

**Note:** This primarily extends the [`@wordpress/eslint-plugin/recommended`](https://github.com/WordPress/gutenberg/tree/trunk/packages/eslint-plugin) ruleset and does not change any of the rules exposed on that plugin. As a base, all WooCommerce projects are expected to follow WordPress JavaScript Code Styles.

However, this ruleset does implement the following (which do not conflict with WordPress standards):

- Using typescript eslint parser to allow for eslint Import ([see issue](https://github.com/gajus/eslint-plugin-jsdoc/issues/604#issuecomment-653962767))
- prettier formatting (using `wp-prettier`)
- Dependency grouping (External and Internal) for dependencies in JavaScript files
- No yoda conditionals
- Radix argument required for `parseInt`.

## Installation

Install the module

```bash
pnpm install @woocommerce/eslint-plugin --save-dev
```

## Usage

To opt-in to the default configuration, extend your own project's `.eslintrc.js` file:

```js
module.exports = {
  "extends": [ "plugin:@woocommerce/eslint-plugin/recommended" ]
}
```

Refer to the [ESLint documentation on Shareable Configs](http://eslint.org/docs/developer-guide/shareable-configs) for more information.

The `recommended` preset will include rules governing an ES2015+ environment, and includes rules from the [`@wordpress/eslint-plugin/recommended`](https://github.com/WordPress/gutenberg/tree/trunk/packages/eslint-plugin) project.

If you want to use prettier in your code editor, you'll need ot create a `.prettierrc.js` file at the root of your project with the following:

```js
module.exports = require("@wordpress/prettier-config");
```

### Rules

| Rule                                                                       | Description                               | Recommended |
| -------------------------------------------------------------------------- | ----------------------------------------- | ----------- |
| [dependency-group](/packages/js/eslint-plugin/docs/rules/dependency-group.md) | Enforce dependencies docblocks formatting | ✓           |
