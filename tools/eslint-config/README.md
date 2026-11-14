# `@woocommerce/eslint-config`

The WooCommerce monorepo's internal ESLint Flat Config layer. This package is private and is never published: it exists so that monorepo-only lint decisions have somewhere to live that is not the public plugin.

## Why this package exists

WooCommerce's lint setup is split across two layers, and the split is the whole point of this package.

| | [`@woocommerce/eslint-plugin`](../../packages/js/eslint-plugin/README.md) | `@woocommerce/eslint-config` |
| --- | --- | --- |
| Published to npm | Yes | No (`private: true`) |
| Consumed by | Third-party extensions, and this package | Packages inside this monorepo only |
| Holds | The portable WooCommerce ruleset | Monorepo-only relaxations and strictness |

The public plugin is consumed by extension authors we cannot see or coordinate with, so it stays **portable**: it assumes no type information and no covering `tsconfig.json`, and its rule severities are deliberate upstream policy for every consumer. Anything that only makes sense inside this repository belongs here instead.

The practical consequence: **when this monorepo needs a rule's severity changed, change it here, not in the plugin.** A relaxation added upstream silently weakens linting for every extension author who installs the published package; the same relaxation here affects only this repository.

## Usage

Each package in the monorepo has its own `eslint.config.mjs` that spreads this config:

```js
/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [ ...woocommerce ];
```

The export is a Flat Config array, so add your own configuration objects after it. Flat Config is last-wins — a later object overrides an earlier one:

```js
/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		rules: {
			'no-console': 'off',
		},
	},
];
```

Add the package to the consuming package's `devDependencies` as `"@woocommerce/eslint-config": "workspace:*"`.

## What it contains

`index.js` spreads `@woocommerce/eslint-plugin`'s `recommended` config and then applies two sets of relaxations:

- **`RELAXED_RULES`** — applied globally.
- **`RELAXED_TEST_RULES`** — applied only to test files. The `jest` and `testing-library` plugins are registered for test files only, so their rules must be relaxed in a block scoped the same way. Referencing them globally fails with `Could not find plugin`.

Both sets are debt, not policy: they are `warn` because their violations are unfixed, not because `warn` is the intended severity. `index.js` annotates them with what causes them to fire. Restoring them to `error` is tracked in [#66078](https://github.com/woocommerce/woocommerce/issues/66078), and is best done a rule family at a time rather than in one sweep.

Because Flat Config is last-wins, a package that sets a severity after spreading this config overrides what is set here, so this file is not the last word on any package's severities. Several do, most substantially `plugins/woocommerce/client/blocks/eslint.config.mjs` and `plugins/woocommerce/eslint.config.mjs` (the playwright rules, whose plugin is only registered for its e2e block). Check the package's own config before assuming a severity came from here.

## Adding a rule

Type-aware rules — anything requiring `languageOptions.parserOptions.projectService`, such as `@typescript-eslint/no-floating-promises` — belong **here**, never in the public plugin. The plugin cannot assume a consuming project has a tsconfig that covers every linted file, but this monorepo can.

Do not register your own copy of `eslint-plugin-import` or `eslint-plugin-react`. Neither supports ESLint v10, and they are consumed only as the `fixupPluginRules`-wrapped instances that `@wordpress/eslint-plugin` registers. A second copy is fatal (`Cannot redefine plugin`), since Flat Config keys plugins by object identity. Configure their rules on the inherited plugins instead.

## Editors

eslint is not publicly hoisted to the repository root — each package declares its own copy, so that a package's resolved version is the one it asks for rather than whichever copy reached the root first. An editor that looks for eslint at the workspace root therefore finds nothing. In VS Code, point the ESLint extension at a workspace that declares it:

```json
"eslint.nodePath": "tools/eslint-config/node_modules"
```

`.vscode/` is gitignored, so this belongs in your own workspace settings rather than in the repository.

The plugin README's [Editors](../../packages/js/eslint-plugin/README.md#editors) section covers `eslint.workingDirectories`, which makes the extension resolve one config per package instead of one from the window root. Both settings are usually wanted together.

## See also

- [`@woocommerce/eslint-plugin`](../../packages/js/eslint-plugin/README.md) — the public ruleset, including its Flat Config migration guide.
- [ESLint Flat Config documentation](https://eslint.org/docs/latest/use/configure/configuration-files)
