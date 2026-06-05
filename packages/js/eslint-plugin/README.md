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

### Flat Config (ESLint v9+)

ESLint v9 and v10 use the new [Flat Config](https://eslint.org/docs/latest/use/configure/configuration-files) format. The plugin exposes a `flat/recommended` array that you can spread into your `eslint.config.js`:

```js
// eslint.config.js
const wc = require( '@woocommerce/eslint-plugin' );

module.exports = [ ...wc.configs[ 'flat/recommended' ] ];
```

The flat-config export is built on top of the same `recommended` preset, so the rules, parser, plugins, settings, and test-file overrides all stay in sync automatically. Legacy `.eslintrc.js` consumers (ESLint v8) should continue to use the `extends: 'plugin:@woocommerce/eslint-plugin/recommended'` syntax shown above.

#### ESLint v9 / v10 caveats

The `flat/recommended` export resolves the legacy `extends` chain through `FlatCompat` from `@eslint/eslintrc`, so the config loads cleanly under ESLint v9+ and can be spread into your `eslint.config.js` without the `TypeError: ... is not iterable` reported in [#65458](https://github.com/woocommerce/woocommerce/issues/65458). A handful of the underlying plugins in this preset (`@typescript-eslint@5.x`, `eslint-plugin-jest@27.x`, `eslint-plugin-testing-library@5.x`) predate ESLint v9 and may throw runtime errors such as `context.getScope is not a function` when their rules run under v9. Bumping those plugin dependencies to v9-compatible majors is tracked separately; in the meantime you can silence individual failing rules with a `rules: { ... }` override in your own `eslint.config.js`.

If you want to use prettier in your code editor, you'll need to create a `.prettierrc.js` file at the root of your project with the following:

```js
module.exports = require("@wordpress/prettier-config");
```

### Rules

| Rule                                                                       | Description                               | Recommended |
| -------------------------------------------------------------------------- | ----------------------------------------- | ----------- |
| [dependency-group](/packages/js/eslint-plugin/docs/rules/dependency-group.md) | Enforce dependencies docblocks formatting | ✓           |
