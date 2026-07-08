# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0](https://www.npmjs.com/package/@woocommerce/admin-layout/v/2.0.0) - 2026-06-11 

-   Major - Update @wordpress/* dependencies to WordPress 6.8 minimum. [#64114]
-   Major - Updated declared dependencies to React 18 and WordPress 6.6. [#53531]
-   Minor - Bump jest package dependency to 29.5.x. [#60324]
-   Minor - Improve build time for admin-layout by using webpack filesystem cache. [#64082]
-   Minor - Remove unused React imports. [#55554]
-   Minor - Upgraded TypeScript in the monorepo to 5.7.2. [#53165]
-   Patch - Fix woo header components types. [#55822]
-   Patch - Bump wireit dependency version to latest. [#57313]
-   Patch - CI: leverage composer packages cache in lint monorepo job. [#52054]
-   Patch - Fix react-18-upgrade TODOs (@ts-expect-error) in WC Admin. [#55478]
-   Patch - Fix the admin layout package installation example. [#65500]
-   Patch - Fix WooFooterItem type. [#54710]
-   Patch - Migrate from React RC types to direct React Props types with jscodeshift codemod. [#56594]
-   Patch - Monorepo: build RAM usage optimization. [#58781]
-   Patch - Monorepo: consolidate @wordpress/babel-preset-default, @wordpress/browserslist-config, glob packages versions. [#56392]
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`. [#58941]
-   Patch - Monorepo: consolidate syncpack config around React 17/18 usage. [#52022]
-   Patch - Monorepo: consolidate TypeScript config files and JS test directories naming. [#52191]
-   Patch - Monorepo: drop the unused `concurrently` package from dependencies. [#58765]
-   Patch - Monorepo: refresh DependencyExtractionWebpackPlugin for compatibility with filesystem cache, admin build cleanup. [#64111]
-   Patch - Monorepo: refresh wireit dependencyOutputs configuration synchronization when installing dependencies. [#55095]
-   Patch - Monorepo: watch startup time optimization. [#59166]
-   Patch - Monorepo: Webpack deps review and consolidation and a bit of deps grooming. [#56746]
-   Patch - Move the CommonJS build to prepack so day-to-day development only builds the ESM output. [#64876]
-   Patch - Move TypeScript type-checking from the build to a new `lint:lang:types` script. Builds now emit types and JS without type-checking. [#65168]
-   Patch - Replaced patched `@wordpress/data` types with opt-in internal package types. [#63483]
-   Patch - Replaced wireit + tsc package build pipeline with a per-package esbuild script. [#65210]
-   Patch - Update dependencies. [#48645]
-   Patch - Update wireit to 0.14.10. [#54996]

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
