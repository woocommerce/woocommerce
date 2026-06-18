# Jest WordPress Version Compat

Jest configuration helper for testing code against the `@wordpress/*` package versions associated with a WordPress target.

The helper prepares a local dependency cache for compatibility runs. Projects keep their normal installed dependencies, while compatibility runs remap selected `@wordpress/*` packages to the cache for the active `WP_VERSION`.

## Usage

Wrap an existing Jest config:

```javascript
const {
	withWordPressDependencyCompat,
} = require( '@woocommerce/jest-wordpress-version-compat' );

module.exports = withWordPressDependencyCompat( {
	testEnvironment: 'jsdom',
} );
```

Select the target with `WP_VERSION`:

```bash
WP_VERSION=latest jest
WP_VERSION=latest-1 jest
WP_VERSION=gutenberg jest
```

Supported targets are:

- `latest`: the newest WordPress npm dist-tag available for each requested package.
- `latest-1`: the previous WordPress npm dist-tag available for each requested package.
- `gutenberg`: the npm `latest` dist-tag.

## Package Selection

By default, the helper reads the closest `package.json` and selects declared `@wordpress/*` dependencies whose version spec starts with `catalog:wp-`. Packages bundled with WordPress but not useful for compatibility remapping are excluded internally.

Pass `packages` when a Jest project needs an explicit package list:

```javascript
const {
	withWordPressDependencyCompat,
} = require( '@woocommerce/jest-wordpress-version-compat' );

module.exports = withWordPressDependencyCompat(
	{
		testEnvironment: 'jsdom',
	},
	{
		packages: [ '@wordpress/data', '@wordpress/components' ],
		wpVersion: process.env.WP_VERSION || 'latest',
	}
);
```

## WooCommerce Monorepo Usage

Most WooCommerce packages should not compose this helper directly. The shared integration lives in `@woocommerce/internal-js-tests/jest-preset.js`.

Run package tests with a compatibility target:

```bash
WP_VERSION=latest pnpm --filter=@woocommerce/components test:js
WP_VERSION=gutenberg pnpm --filter=@woocommerce/components test:js
```

The root monorepo scripts run every package that exposes `test:js`:

```bash
pnpm test:js:wp-latest
pnpm test:js:wp-latest-1
pnpm test:js:gutenberg
```

Packages that do not use the shared preset must compose `withWordPressDependencyCompat()` themselves before they participate in compatibility remapping.

## Cache

Cached packages are installed under:

```text
node_modules/.cache/jest-wordpress-version-compat/<wp-version>/
```
