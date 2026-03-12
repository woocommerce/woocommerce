# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0](https://www.npmjs.com/package/@woocommerce/tracks/v/1.5.0) - 2026-02-23 

-   Minor - Add bumpStats and fix unit test tooling [#50155]
-   Patch - Comment: Fix some comment typos. [#50993]
-   Minor - Bump jest package dependency to 29.5.x [#60324]
-   Patch - Bump wireit dependency version to latest. [#57299]
-   Patch - CI: liverage composer packages cache in lint monorepo job [#52054]
-   Patch - Fix pnpm version to 9.1.3 to avoid dependency installation issues. [#50828]
-   Patch - Loosens the Node.js engine constraint. [#63406]
-   Patch - Monorepo: build RAM usage optimization. [#59046]
-   Minor - Monorepo: bump pnpm version to 9.15.0 [#54189]
-   Patch - Monorepo: consolidate @babel/* dependencies versions across the monorepo. [#56575]
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`. [#58941]
-   Patch - Monorepo: consolidate syncpack config around React 17/18 usage. [#52022]
-   Patch - Monorepo: consolidate TypeScript config files and JS test directories naming. [#52191]
-   Patch - Monorepo: drop the unused `concurrently` package from dependencies. [#58765]
-   Patch - Monorepo: refresh wireit dependencyOutputs configuration synchronization when installing dependencies. [#55095]
-   Patch - Update wireit to 0.14.10 [#54996]
-   Minor - Upgraded Typescript in the monorepo to 5.7.2 [#53165]

## [1.4.0](https://www.npmjs.com/package/@woocommerce/tracks/v/1.4.0) - 2024-06-11 

-   Minor - Add recordEvent validation to Tracks package #34005 [#34005]
-   Minor - Bump node version. [#45148]
-   Minor - Adjust build/test scripts to remove -- -- that was required for pnpm 6. [#34661]
-   Minor - Fix node and pnpm versions via engines [#34773]
-   Minor - Match TypeScript version with syncpack [#34787]
-   Minor - Update pnpm monorepo-wide to 8.6.5 [#38990]
-   Minor - Update pnpm to 8.6.7 [#39245]
-   Minor - Update pnpm to version 8. [#37915]
-   Minor - Update pnpm version constraint to 7.13.3 to avoid auto-install-peers issues [#35007]
-   Minor - Upgrade TypeScript to 5.1.6 [#39531]
-   Patch - bump php version in packages/js/*/composer.json [#42020]
-   Patch - Make eslint emit JSON report for annotating PRs. [#39704]
-   Patch - Update eslint to 8.32.0 across the monorepo. [#36700]
-   Patch - Update pnpm to 9.1.0 [#47385]

## [1.3.0](https://www.npmjs.com/package/@woocommerce/tracks/v/1.3.0) - 2022-07-08 

-   Minor - Remove PHP and Composer dependencies for packaged JS packages

## [1.2.0](https://www.npmjs.com/package/@woocommerce/tracks/v/1.2.0) - 2022-06-15 

-   Minor - Add Jetpack Changelogger
-   Minor - Convert package to Typescript.
-   Patch - Standardize lint scripts: add lint:fix

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/tracks/CHANGELOG.md).
