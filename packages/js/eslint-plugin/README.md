# ESLint Plugin

This is an [ESLint](https://eslint.org/) plugin including configurations for WooCommerce development.

**Note:** This primarily extends the [`@wordpress/eslint-plugin/recommended`](https://github.com/WordPress/gutenberg/tree/trunk/packages/eslint-plugin) ruleset and does not change any of the rules exposed on that plugin. As a base, all WooCommerce projects are expected to follow WordPress JavaScript Code Styles.

However, this ruleset does implement the following (which do not conflict with WordPress standards):

- Using typescript eslint parser to allow for eslint Import ([see issue](https://github.com/gajus/eslint-plugin-jsdoc/issues/604#issuecomment-653962767))
- prettier formatting (using `wp-prettier`)
- Import grouping (external before internal) via `import/order`
- No yoda conditionals
- Radix argument required for `parseInt`.

## Requirements

This package is **Flat Config only**. It requires:

- ESLint `^9.22.0 || ^10.0.0` (9.22 ships the `eslint/config` helpers this uses)
- `prettier` `>=3` (WooCommerce uses `wp-prettier@3`)
- `typescript` `>=5`

There is no `.eslintrc` support and no compatibility wrapper. If you are still on
ESLint 8, stay on `@woocommerce/eslint-plugin@3`.

## Installation

Install the module

```bash
pnpm install @woocommerce/eslint-plugin --save-dev
```

## Usage

Create an `eslint.config.mjs` at the root of your project and spread the
`recommended` config, which is a Flat Config array:

```js
import woocommerce from '@woocommerce/eslint-plugin';

export default [ ...woocommerce.configs.recommended ];
```

Add your own configuration objects after it: Flat Config is last-wins, so later
objects override earlier ones.

A root `eslint.config.mjs` already covers every file beneath it — ESLint searches
upward from each file for the nearest one, so most projects need only this. Add a
config to a subdirectory only when that subtree needs a different one. Note that
it replaces the ancestor rather than merging with it, so it has to spread
`recommended` itself.

```js
import woocommerce from '@woocommerce/eslint-plugin';

export default [
	{ ignores: [ 'build/**' ] },
	...woocommerce.configs.recommended,
	{
		rules: {
			'no-console': 'off',
		},
	},
];
```

Refer to the [ESLint documentation on Flat Config](https://eslint.org/docs/latest/use/configure/configuration-files) for more information.

### Do not register `import` or `react` yourself

`eslint-plugin-import` and `eslint-plugin-react` do not support ESLint v10.
`recommended` consumes them through the `fixupPluginRules`-wrapped instances that
`@wordpress/eslint-plugin` registers. If you register your own copy, its rules
will throw at lint time, and ESLint will report `Cannot redefine plugin` because
Flat Config keys plugins by object identity. Configure their rules on the
inherited plugins instead of re-registering them.

### Prettier

If you want to use prettier in your code editor, you'll need to create a `.prettierrc.js` file at the root of your project with the following:

```js
module.exports = require( '@wordpress/prettier-config' );
```

### Editors

The VS Code ESLint extension resolves Flat Config automatically from version
3.0.10 onwards. In a monorepo, set `eslint.workingDirectories` to `[ { "mode":
"auto" } ]` so it resolves one config per package rather than from the window
root.

## Migrating from v3

`v3` exported an eslintrc config object and peered ESLint 8. `v4` exports Flat
Config arrays and peers ESLint `^9.22 || ^10`.

- Replace `.eslintrc.js` with `eslint.config.mjs`, and
  `extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ]` with
  `...woocommerce.configs.recommended`.
- `.eslintignore` is not read by Flat Config. Move its patterns into an
  `ignores` array.
- `overrides` entries become their own `files`-scoped config objects, and
  `excludedFiles` becomes `ignores` alongside `files` in the same object.
- `env` becomes `languageOptions.globals`, most easily via the `globals` package.
- `root: true` has no equivalent and can be deleted.
- The `@woocommerce/dependency-group` rule was removed in v3.1. Import grouping
  is enforced by `import/order`, which no longer requires the
  `/* External dependencies */` comment blocks.
