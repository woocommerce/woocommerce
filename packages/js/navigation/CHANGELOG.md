# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
-    - Upgraded react-router-dom to v6, which itself causes breaking changes. This upgrade will require consumers to also upgrade their react-router-dom to v6. #33156

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/navigation/CHANGELOG.md).
