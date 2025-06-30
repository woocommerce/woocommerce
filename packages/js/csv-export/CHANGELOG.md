# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.10.0](https://www.npmjs.com/package/@woocommerce/csv-export/v/1.10.0) - 2024-12-19 

-   Patch - Improved CSV export usability by allowing negative numeric values to be unescaped while continuing to escape potentially risky strings to prevent CSV Injection (client-side CSV exporting). [#52727]
-   Patch - CI: liverage composer packages cache in lint monorepo job [#52054]
-   Patch - Fix pnpm version to 9.1.3 to avoid dependency installation issues. [#50828]
-   Patch - Monorepo: consolidate syncpack config around React 17/18 usage. [#52022]
-   Patch - Monorepo: consolidate TypeScript config files and JS test directories naming. [#52191]
-   Minor - Upgraded Typescript in the monorepo to 5.7.2 [#53165]

## [1.9.0](https://www.npmjs.com/package/@woocommerce/csv-export/v/1.9.0) - 2024-06-11 

-   Minor - Bump node version. [#45148]
-   Minor - Remove moment dependency from the csv-export package. [#45410]
-   Patch - bump php version in packages/js/*/composer.json [#42020]
-   Patch - Update events that should trigger the test job(s) [#47612]
-   Patch - Update pnpm to 9.1.0 [#47385]

## [1.8.0](https://www.npmjs.com/package/@woocommerce/csv-export/v/1.8.0) - 2023-11-23 

-   Patch - Use single quote instead of tab for escaping in CSV exports. [#41163]
-   Patch - Add missing type definitions and add babel config for tests [#34428]
-   Patch - Make eslint emit JSON report for annotating PRs. [#39704]
-   Patch - Merging trunk with local [#34322]
-   Patch - Update eslint to 8.32.0 across the monorepo. [#36700]
-   Minor - Adjust build/test scripts to remove -- -- that was required for pnpm 6. [#34661]
-   Minor - Fix node and pnpm versions via engines [#34773]
-   Minor - Match TypeScript version with syncpack [#34787]
-   Minor - Update pnpm monorepo-wide to 8.6.5 [#38990]
-   Minor - Update pnpm to 8.6.7 [#39245]
-   Minor - Update pnpm to version 8. [#37915]
-   Minor - Update pnpm version constraint to 7.13.3 to avoid auto-install-peers issues [#35007]
-   Minor - Upgrade TypeScript to 5.1.6 [#39531]

## [1.7.0](https://www.npmjs.com/package/@woocommerce/csv-export/v/1.7.0) - 2022-07-08 

-   Minor - Remove PHP and Composer dependencies for packaged JS packages

## [1.6.0](https://www.npmjs.com/package/@woocommerce/csv-export/v/1.6.0) - 2022-06-14 

-   Minor - Add Jetpack Changelogger
-   Patch - Migrate @woocommerce/csv-export to TS
-   Patch - Standardize lint scripts: add lint:fix

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/csv-export/CHANGELOG.md).
