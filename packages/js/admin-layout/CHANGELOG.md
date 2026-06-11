# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0](https://www.npmjs.com/package/@woocommerce/admin-layout/v/2.0.0) - 2026-06-11 

-   Patch - Fix woo header components types
-   Major [ **BREAKING CHANGE** ] - Update @wordpress/* dependencies to wp-6.8 minimum.
-   Minor - Bump jest package dependency to 29.5.x
-   Patch - Bump wireit dependency version to latest.
-   Patch - CI: liverage composer packages cache in lint monorepo job
-   Patch - Fix react-18-upgrade TODOs (@ts-expect-error) in WC Admin
-   Patch - Fix the admin layout package installation example.
-   Patch - Fix WooFooterItem type
-   Minor - Improve build time for admin-layout by using webpack filesystem cache.
-   Patch - Migrate from React RC types to direct React Props types with jscodeshift codemod
-   Patch - Monorepo: build RAM usage optimization.
-   Patch - Monorepo: consolidate @wordpress/babel-preset-default, @wordpress/browserslist-config, glob packages versions.
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`.
-   Patch - Monorepo: consolidate syncpack config around React 17/18 usage.
-   Patch - Monorepo: consolidate TypeScript config files and JS test directories naming.
-   Patch - Monorepo: drop the unused `concurrently` package from dependencies.
-   Patch - Monorepo: refresh DependencyExtractionWebpackPlugin for compatibility with filesystem cache, admin build cleanup.
-   Patch - Monorepo: refresh wireit dependencyOutputs configuration synchronization when installing dependencies.
-   Patch - Monorepo: watch startup time optimization.
-   Patch - Monorepo: Webpack deps review and consolidation and a bit of deps grooming
-   Patch - Move the CommonJS build to prepack so day-to-day development only builds the ESM output.
-   Patch - Move TypeScript type-checking from the build to a new `lint:lang:types` script. Builds now emit types and JS without type-checking.
-   Minor - Remove unused React imports
-   Patch - Replaced patched `@wordpress/data` types with opt-in internal package types.
-   Patch - Replaced wireit + tsc package build pipeline with a per-package esbuild script.
-   Major [ **BREAKING CHANGE** ] - Updated declared dependencies to React 18 and Wordpress 6.6
-   Patch - Update dependencies
-   Patch - Update wireit to 0.14.10
-   Minor - Upgraded Typescript in the monorepo to 5.7.2

## [1.1.0](https://www.npmjs.com/package/@woocommerce/admin-layout/v/1.1.0) - 2024-04-12 

-   Patch - Corrected build configuration for packages that weren't outputting minified code. [#43716]
-   Patch - bump php version in packages/js/*/composer.json [#42020]
-   Patch - Add missing dev dependency
-   Minor - Add useAdminSidebarWidth hook [#44132]

## [1.0.0](https://www.npmjs.com/package/@woocommerce/admin-layout/v/1.0.0) - 2023-11-28 

-   Patch - Update dependencies.
-   Patch - Make eslint emit JSON report for annotating PRs. [#39704]
-   Patch - Update webpack config to use @woocommerce/internal-style-build's parser config [#37195]
-   Minor - Adding LayoutContext component and hook. [#37720]
-   Minor - Adding support for modifying fill name to WooHeaderItem. [#37255]
-   Minor - Create @woocommerce/admin-layout package to house header, footer, and similar components and utilities. [#37094]
-   Minor - Upgrade TypeScript to 5.1.6 [#39531]

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/admin-layout/CHANGELOG.md).
