# Jest WordPress Version Compat

Jest configuration helper for testing code against the `@wordpress/*` package versions bundled with a target WordPress version.

The package keeps version metadata small and prepares a local dependency cache only when Jest needs it. This avoids installing every supported WordPress package version up front.

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

Select the package set with `WP_VERSION`:

```bash
WP_VERSION=latest jest
WP_VERSION=latest-1 jest
WP_VERSION=gutenberg jest
```

The project under test is expected to install its normal `@wordpress/*` dependencies from `package.json`. In WooCommerce Blocks, those dependencies already represent the previous WordPress package baseline, so compatibility runs focus on newer external targets instead of retesting the installed baseline.

When Jest loads the config, the helper:

-   Reads the closest `package.json`.
-   Finds declared `@wordpress/*` dependencies.
-   Excludes WordPress packages that are bundled with WordPress but should not be remapped by the compatibility layer.
-   Resolves each package version from npm metadata for the selected WordPress target.
-   Installs missing packages into a local cache.
-   Adds Jest `moduleNameMapper` entries that point those packages to the cache.

Cached packages are installed under:

```text
node_modules/.cache/jest-wordpress-version-compat/<wp-version>/
```

## Explicit Packages

Pass a package list when a Jest project needs a narrower or broader set than the closest `package.json` declares:

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

## Configuration

Add configuration to `package.json` when the package list should be shared by multiple Jest configs:

```json
{
	"wpDependencyCompat": {
		"packages": [ "@wordpress/data", "@wordpress/components" ]
	}
}
```

## WooCommerce Monorepo Usage

WooCommerce packages should not wire this helper into individual Jest configs. The monorepo-level integration lives in `@woocommerce/internal-js-tests/jest-preset.js`, which is already used by most package-level Jest configs.

Set `WP_VERSION` when running package tests to enable compatibility resolution through the shared preset:

```bash
WP_VERSION=latest pnpm --filter=@woocommerce/components test:js
WP_VERSION=gutenberg pnpm --filter=@woocommerce/components test:js
```

When `WP_VERSION` is not set, the shared preset runs with the installed `@wordpress/*` dependencies and prints a warning that the test run may not rely on a validated WordPress package environment.

Packages that do not use the shared preset need to compose the helper explicitly or move to the shared preset before they participate in the monorepo-wide compatibility run.

The root monorepo scripts run every package that exposes `test:js`:

```bash
pnpm test:js:wp-latest
pnpm test:js:wp-latest-1
pnpm test:js:gutenberg
```

CI expands JavaScript unit-test jobs into three variants:

-   The normal installed dependency set from `package.json`.
-   `WP_VERSION=latest`.
-   `WP_VERSION=gutenberg`.

The compatibility variants use the same package-level `test:js` command. Packages that do not use the shared Jest preset still run in those jobs, but their dependencies are not remapped until they compose this helper.

## Bundled Package Exclusions

The helper excludes these packages from compatibility remapping:

```javascript
const BUNDLED_PACKAGES = [
	'@wordpress/admin-ui',
	'@wordpress/dataviews',
	'@wordpress/dataviews/wp',
	'@wordpress/fields',
	'@wordpress/grid',
	'@wordpress/icons',
	'@wordpress/interface',
	'@wordpress/style-runtime',
	'@wordpress/ui',
	'@wordpress/undo-manager',
	'@wordpress/views',
];
```

These packages are bundled with WordPress or Gutenberg but are not reliable indicators of the WordPress core package set under test. Some are newer editor packages, some expose subpath imports, and some, such as `@wordpress/icons`, are intentionally consumed as bundled UI assets. Remapping them through the compatibility cache can create duplicate package instances or snapshot churn that does not represent a real WordPress-version compatibility issue.

Keep these packages resolved by the project under test. The compatibility layer should focus on the WordPress packages whose npm dist-tags model the target WordPress dependency set.

## Offline Mode

Set `WP_JEST_DEPENDENCY_COMPAT_OFFLINE=1` to fail when the selected packages are missing from cache:

```bash
WP_JEST_DEPENDENCY_COMPAT_OFFLINE=1 WP_VERSION=latest jest
```

This is useful in CI jobs that restore the cache before running tests.

## Version Targets

The package resolves version targets from npm metadata instead of keeping a hardcoded package-version table:

-   `latest`, using npm metadata to find the highest `wp-*` WordPress dist-tag on each requested `@wordpress/*` package.
-   `latest-1`, using npm metadata to find the previous `wp-*` WordPress dist-tag on each requested `@wordpress/*` package.
-   `gutenberg`, using the npm `latest` dist-tag for the current Gutenberg package line.

This means developers can run tests against the installed baseline, the current WordPress package set, and the Gutenberg package line without changing this package first. If npm has not published enough matching `wp-*` dist-tags for a requested package, the helper fails during cache preparation instead of falling back to a guessed version.
