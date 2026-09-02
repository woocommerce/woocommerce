# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0](https://www.npmjs.com/package/@woocommerce/eslint-plugin/v/4.0.0) - 2026-08-06 

-   Major [ **BREAKING CHANGE** ] - Add ESLint v9/v10 Flat Config support. `configs.recommended` is now a Flat Config array rather than an eslintrc object, the `eslint` peer range is `^9.22.0 || ^10.0.0`, and prettier `>=3` is required. There is no eslintrc compatibility wrapper: consumers must migrate to `eslint.config.mjs`. Do not register `eslint-plugin-import` or `eslint-plugin-react` yourself; neither supports ESLint v10, and `recommended` provides the wrapped instances. [#66621]
-   Major [ **BREAKING CHANGE** ] - Replace the `@woocommerce/dependency-group` rule with `import/order`. The custom rule is removed (rule, test, docs, and export); the recommended config now enforces import grouping via `import/order` and no longer requires the `/* External/Internal dependencies */` comment blocks. [#66086]

## [3.0.0](https://www.npmjs.com/package/@woocommerce/eslint-plugin/v/3.0.0) - 2026-06-10 

-   Major [ **BREAKING CHANGE** ] - Remove unused React imports from ESLint config; consuming projects may see new lint errors. [#55554]
-   Minor - Bump jest package dependency to 29.5.x. [#60324]
-   Minor - Fix typos in README.md files. [#48569]
-   Minor - Monorepo: bump pnpm version to 9.15.0. [#54189]
-   Minor - Upgraded TypeScript in the monorepo to 5.7.2. [#53165]
-   Patch - Bump wireit dependency version to latest. [#57299]
-   Patch - CI: leverage composer packages cache in lint monorepo job. [#52054]
-   Patch - Fix pnpm version to 9.1.3 to avoid dependency installation issues. [#50828]
-   Patch - Monorepo: consolidate @babel/* dependencies versions across the monorepo. [#56575]
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`. [#58941]
-   Patch - Monorepo: consolidate syncpack config around React 17/18 usage. [#52022]
-   Patch - Removed stray wireit devDependency. [#65210]
-   Patch - Update wireit to 0.14.10. [#54996]

## [2.3.0](https://www.npmjs.com/package/@woocommerce/eslint-plugin/v/2.3.0) - 2024-06-11 

-   Minor - Update deps and fix a bug where package rc files were not respected. [#36988]
-   Minor - Warn for jsdoc errors, use wp-prettier [#38523]
-   Minor - Bump node version. [#45148]
-   Minor - Update i18n-text-domain rule to only allow woocommerce text domain [#33780]
-   Minor - Fix node and pnpm versions via engines [#34773]
-   Minor - Match TypeScript version with syncpack [#34787]
-   Minor - Sync @wordpress package versions via syncpack. [#37034]
-   Minor - Update pnpm monorepo-wide to 8.6.5 [#38990]
-   Minor - Update pnpm to 8.6.7 [#39245]
-   Minor - Update pnpm to version  8. [#37915]
-   Minor - Update pnpm version constraint to 7.13.3 to avoid auto-install-peers issues [#35007]
-   Minor - Upgrade TypeScript to 5.1.6 [#39531]
-   Patch - bump php version in packages/js/*/composer.json [#42020]
-   Patch - Fixed some i18n related lint rule violations. [#41450]
-   Patch - Tell eslint-react-plugin to assume React version 17.0.2 [#38512]
-   Patch - Update eslint to 8.32.0 across the monorepo. [#36700]
-   Patch - Update pnpm to 9.1.0 [#47385]

## [2.2.0](https://www.npmjs.com/package/@woocommerce/eslint-plugin/v/2.2.0) - 2022-07-08 

-   Minor - Allow unused destructured variables in lint rules #35548
-   Minor - Remove PHP and Composer dependencies for packaged JS packages

## [2.1.0](https://www.npmjs.com/package/@woocommerce/eslint-plugin/v/2.1.0) - 2022-06-14 

-   Minor - Add Jetpack Changelogger
-   Patch - Standardize lint scripts: add lint:fix

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/eslint-plugin/CHANGELOG.md).
