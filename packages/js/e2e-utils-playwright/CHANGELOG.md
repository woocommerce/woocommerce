# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.5.0](https://www.npmjs.com/package/@woocommerce/e2e-utils-playwright/v/0.5.0) - 2026-06-11 

-   Minor - Enable the Interactivity API-powered Mini Cart by default. [#60823]
-   Minor - Bump jest package dependency to 29.5.x. [#60324]
-   Minor - Converted the package source code to TypeScript. [#63102]
-   Patch - Make checkout address field helpers open collapsed address forms before filling fields. [#65548]
-   Patch - Move the CommonJS build to prepack so day-to-day development only builds the ESM output. [#64876]
-   Patch - Move TypeScript type-checking from the build to a new `lint:lang:types` script. Builds now emit types and JS without type-checking. [#65168]
-   Patch - Replaced wireit + tsc package build pipeline with a per-package esbuild script. [#65210]
-   Patch - Wait for the Gutenberg editor canvas iframe before falling back to the page context in Playwright tests. [#65532]

## [0.4.0](https://www.npmjs.com/package/@woocommerce/e2e-utils-playwright/v/0.4.0) - 2025-07-08 

-   Minor - Added api-client module [#59409]
-   Patch - Monorepo: build RAM usage optimization. [#58861]
-   Patch - Monorepo: consolidate @babel/* dependencies versions across the monorepo. [#56575]
-   Patch - Monorepo: consolidate @wordpress/babel-preset-default, @wordpress/browserslist-config, glob packages versions. [#56392]
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`. [#58941]

## [0.3.1](https://www.npmjs.com/package/@woocommerce/e2e-utils-playwright/v/0.3.1) - 2025-03-06 

-   Patch - Updated the editor canvas frame locator to support changes in Gutenberg 20.6 [#56083]
-   Patch - Updates to support WP6.8 [#56121]
-   Patch - Update checkout utils to fill the phone number and to ignore skip the country field if no country data is specified [#55695]
-   Patch - Monorepo: fix broken E2E packages builds [#55907]

## [0.3.0](https://www.npmjs.com/package/@woocommerce/e2e-utils-playwright/v/0.3.0) - 2025-02-06 

-   Patch - Tweak editor url to correctly resolve for multisite subdirectory site URLs [#55077]
-   Patch - Improve the scope of Checkout E2E utils [#55073]
-   Minor - Monorepo: bump pnpm version to 9.15.0 [#54189]

## [0.2.1](https://www.npmjs.com/package/@woocommerce/e2e-utils-playwright/v/0.2.1) - 2024-12-18 

-   Patch - Improve Checkout Utils for Playwright [#53557]

## [0.2.0](https://www.npmjs.com/package/@woocommerce/e2e-utils-playwright/v/0.2.0) - 2024-12-09 

-   Minor - Port a fix for editor util with Gutenberg installed (original fix in PR 53294) [#53511]
-   Minor - Fixed the homepage URL and the initial release URL in the changelog [#53061]
-   Patch - Added unit tests configuration and created tests for getOrderIdFromUrl function [#52997]
-   Patch - change entry point to build/index.js [#53474]

## [0.1.0](https://www.npmjs.com/package/@woocommerce/e2e-utils-playwright/v/0.1.0) - 2024-11-21

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/e2e-utils-playwright/CHANGELOG.md).
