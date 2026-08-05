# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [9.0.0](https://www.npmjs.com/package/@woocommerce/navigation/v/9.0.0) - 2026-06-10 

-   Major - Remove deprecated Navigation SlotFill; any consumer registering or rendering into it will silently stop working. [#50190]
-   Major - Remove WooCommerce Navigation client side feature and deprecate PHP classes. [#50190]
-   Major - Update @wordpress/* dependencies to WordPress 6.8 minimum. [#64114]
-   Major - Updated declared dependencies to React 18 and WordPress 6.6. [#53531]
-   Minor - Added `getQueryExcludedScreensUrlUpdate` to exclude updating onclick handler for specific screens. [#58209]
-   Minor - Bump jest package dependency to 29.5.x. [#60324]
-   Minor - Fix typos in inline documentation. [#48640]
-   Minor - Fix typos in README.md files. [#48569]
-   Minor - Monorepo: bump pnpm version to 9.15.0. [#54189]
-   Minor - Upgraded TypeScript in the monorepo to 5.7.2. [#53165]
-   Patch - Bump wireit dependency version to latest. [#57299]
-   Patch - CI: code style fixes to pass linting in updated CI environment. [#49020]
-   Patch - CI: leverage composer packages cache in lint monorepo job. [#52054]
-   Patch - Clean up CI job config options; remove unused cascading keys. [#55863]
-   Patch - Fix pnpm version to 9.1.3 to avoid dependency installation issues. [#50828]
-   Patch - Monorepo: address circular dependencies surfaced by SWC TDZ. [#64797]
-   Patch - Monorepo: build RAM usage optimization. [#59001]
-   Patch - Monorepo: consolidate @babel/* dependencies versions across the monorepo. [#56575]
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`. [#58941]
-   Patch - Monorepo: consolidate syncpack config around React 17/18 usage. [#52022]
-   Patch - Monorepo: consolidate TypeScript config files and JS test directories naming. [#52191]
-   Patch - Monorepo: drop the unused `concurrently` package from dependencies. [#58765]
-   Patch - Monorepo: refresh wireit dependencyOutputs configuration synchronization when installing dependencies. [#55095]
-   Patch - Move the CommonJS build to prepack so day-to-day development only builds the ESM output. [#64876]
-   Patch - Move TypeScript type-checking from the build to a new `lint:lang:types` script. Builds now emit types and JS without type-checking. [#65168]
-   Patch - Replaced patched `@wordpress/data` types with opt-in internal package types. [#63483]
-   Patch - Replaced wireit + tsc package build pipeline with a per-package esbuild script. [#65210]
-   Patch - Update wireit to 0.14.10. [#54996]

## [8.2.0](https://www.npmjs.com/package/@woocommerce/navigation/v/8.2.0) - 2024-06-11 

-   Patch - Added in missing TS definitions in package.json [#34154]
-   Minor - Added useQuery hook for usage in React functional components [#34183]
-   Minor - Add hook to check unsaved form changes before page navigation [#36752]
-   Patch - Add __experimentalLocationStack prop to history [#46665]
-   Minor - Bump node version. [#45148]
-   Patch - bump php version in packages/js/*/composer.json [#42020]
-   Patch - Add missing type definitions and add babel config for tests [#34428]
-   Minor - Adjust build/test scripts to remove -- -- that was required for pnpm 6. [#34661]
-   Minor - Fix lint issues [#36988]
-   Minor - Fix node and pnpm versions via engines [#34773]
-   Minor - Fix return value on parseAdminUrl [#36235]
-   Patch - Make eslint emit JSON report for annotating PRs. [#39704]
-   Minor - Match TypeScript version with syncpack [#34787]
-   Minor - Sync @wordpress package versions via syncpack. [#37034]
-   Patch - Update eslint to 8.32.0 across the monorepo. [#36700]
-   Patch - Update events that should trigger the test job(s) [#47612]
-   Minor - Update pnpm monorepo-wide to 8.6.5 [#38990]
-   Minor - Update pnpm to 8.6.7 [#39245]
-   Patch - Update pnpm to 9.1.0 [#47385]
-   Minor - Update pnpm to version 8. [#37915]
-   Minor - Update pnpm version constraint to 7.13.3 to avoid auto-install-peers issues [#35007]
-   Minor - Upgrade TypeScript to 5.1.6 [#39531]

## [8.1.0](https://www.npmjs.com/package/@woocommerce/navigation/v/8.1.0) - 2022-07-08 

-   Minor - Remove PHP and Composer dependencies for packaged JS packages

## [8.0.0](https://www.npmjs.com/package/@woocommerce/navigation/v/8.0.0) - 2022-06-15 

-   Minor - Add Jetpack Changelogger
-   Minor - Update dependency `@wordpress/hooks` to ^3.5.0
-   Minor - Added Typescript type declarations. #32615
-   Minor - Update dependency `history` to ^5.3.0
-   Patch - Standardize lint scripts: add lint:fix
-   Patch - Update dependency history to ^5.3.0
-   Major - Upgraded react-router-dom to v6, which itself causes breaking changes. This upgrade will require consumers to also upgrade their react-router-dom to v6. #33156

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/navigation/CHANGELOG.md).
