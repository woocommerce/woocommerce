# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0](https://www.npmjs.com/package/@woocommerce/experimental/v/4.0.0) - 2026-06-11 

-   Major - Update @wordpress/* dependencies to WordPress 6.8 minimum. [#64114]
-   Major - Updated declared dependencies to React 18 and WordPress 6.6. [#53531]
-   Minor - Adds an 'in progress' state to WooCommerce onboarding tasks, providing visual feedback when tasks are started but not completed. Initially implemented for the Payments setup task. [#59853]
-   Minor - Improve build time for experimental by using webpack filesystem cache. [#64082]
-   Minor - Monorepo: bump pnpm version to 9.15.0. [#54189]
-   Minor - Replace react-visibility-sensor with react-intersection-observer to fix deprecated findDOMNode Warning. [#55362]
-   Minor - Update storybook file format in support with Storybook 7 story indexer. [#51168]
-   Minor - Upgraded TypeScript in the monorepo to 5.7.2. [#53165]
-   Minor - Use @woocommerce/sanitize package to sanitize input. [#60324]
-   Patch - Bump wireit dependency version to latest. [#57299]
-   Patch - CI: leverage composer packages cache in lint monorepo job. [#52054]
-   Patch - Clean up CI job config options; remove unused cascading keys. [#55863]
-   Patch - experimental package: fix TS errors. [#54160]
-   Patch - Fix pnpm version to 9.1.3 to avoid dependency installation issues. [#50828]
-   Patch - Fix task item type. [#54711]
-   Patch - Fixed stretched images in Inbox notes. [#58131]
-   Patch - Migrate from React RC types to direct React Props types with jscodeshift codemod. [#56594]
-   Patch - Monorepo: address circular dependencies surfaced by SWC TDZ. [#64797]
-   Patch - Monorepo: build RAM usage optimization. [#58861]
-   Patch - Monorepo: complete migration from `classnames` package to `clsx`. [#58699]
-   Patch - Monorepo: consolidate @babel/* dependencies versions across the monorepo. [#56575]
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`. [#58941]
-   Patch - Monorepo: consolidate syncpack config around React 17/18 usage. [#52022]
-   Patch - Monorepo: consolidate TypeScript config files and JS test directories naming. [#52191]
-   Patch - Monorepo: drop the unused `concurrently` package from dependencies. [#58765]
-   Patch - Monorepo: refresh DependencyExtractionWebpackPlugin for compatibility with filesystem cache, admin build cleanup. [#64111]
-   Patch - Monorepo: refresh wireit dependencyOutputs configuration synchronization when installing dependencies. [#55095]
-   Patch - Monorepo: used babel transforms cleanup. [#56486]
-   Patch - Monorepo: watch startup time optimization. [#59166]
-   Patch - Monorepo: Webpack deps review and consolidation and a bit of deps grooming. [#56746]
-   Patch - Move the CommonJS build to prepack so day-to-day development only builds the ESM output. [#64876]
-   Patch - Move TypeScript type-checking from the build to a new `lint:lang:types` script. Builds now emit types and JS without type-checking. [#65168]
-   Patch - Prevent widows in inbox note and task list description copy with text-wrap: pretty. [#64453]
-   Patch - Remove "banner" layout test case. [#52088]
-   Patch - Replaced patched `@wordpress/data` types with opt-in internal package types. [#63483]
-   Patch - Replaced wireit + tsc package build pipeline with a per-package esbuild script. [#65210]
-   Patch - Stop rendering thumbnail images in inbox notes. Drop `image` and `layout` from the InboxNoteCard prop type and remove the `.thumbnail` and `.banner` styles. [#64443]
-   Patch - Update dompurify to 2.5.7. [#52961]
-   Patch - Update wireit to 0.14.10. [#54996]

## [3.3.0](https://www.npmjs.com/package/@woocommerce/experimental/v/3.3.0) - 2024-07-25 

-   Patch - Added in missing TS definitions in package.json [#34154]
-   Patch - Check for note actions before checking length [#35396]
-   Patch - Corrected build configuration for packages that weren't outputting minified code. [#43716]
-   Patch - Fix invalid return callback ref warning [#37655]
-   Patch - Fix Launch Your Store task item should not be clickable once completed [#46361]
-   Patch - Fix missing fills prop in useSlotFills return object for wp.components >= 21.2.0 [#36887]
-   Patch - Fix remote inbox layout overflows the page width [#47451]
-   Minor - Bump node version. [#45148]
-   Patch - bump php version in packages/js/*/composer.json [#42020]
-   Patch - Support direction prop to control which direction hidden items open. [#36806]
-   Patch - update references to woocommerce.com to now reference woo.com [#41241]
-   Patch - Update TaskItem to include a badge next to the title. Update also related components TaskList and SetupTaskList, as well as docs, storybook, and tests. [#40034]
-   Patch - Update Woo.com references to WooCommerce.com. [#46259]
-   Patch - Add missing type definitions and add babel config for tests [#34428]
-   Minor - Adjust build/test scripts to remove -- -- that was required for pnpm 6. [#34661]
-   Minor - Fix lint issues [#36988]
-   Minor - Fix node and pnpm versions via engines [#34773]
-   Minor - Improve the "Dismiss" button visibility [#35060]
-   Patch - Make eslint emit JSON report for annotating PRs. [#39704]
-   Minor - Match TypeScript version with syncpack [#34787]
-   Patch - Merging trunk with local [#34322]
-   Minor - Sync @wordpress package versions via syncpack. [#37034]
-   Patch - Update dependencies [#48645]
-   Patch - Update eslint to 8.32.0 across the monorepo. [#36700]
-   Patch - Update events that should trigger the test job(s) [#47612]
-   Minor - Update pnpm monorepo-wide to 8.6.5 [#38990]
-   Minor - Update pnpm to 8.6.7 [#39245]
-   Patch - Update pnpm to 9.1.0 [#47385]
-   Minor - Update pnpm to version 8. [#37915]
-   Minor - Update pnpm version constraint to 7.13.3 to avoid auto-install-peers issues [#35007]
-   Patch - Update webpack config to use @woocommerce/internal-style-build's parser config [#37195]
-   Patch - Upgraded Storybook to 6.5.17-alpha.0 for TypeScript 5 compatibility [#39745]
-   Minor - Upgrade TypeScript to 5.1.6 [#39531]
-   Patch - Correct spelling errors [#37887]

## [3.2.0](https://www.npmjs.com/package/@woocommerce/experimental/v/3.2.0) - 2022-07-08 

-   Minor - Remove PHP and Composer dependencies for packaged JS packages

## [3.1.0](https://www.npmjs.com/package/@woocommerce/experimental/v/3.1.0) - 2022-06-14 

-   Minor - Add Jetpack Changelogger
-   Minor - Update TaskItem props type definition.
-   Minor - Fix setup task list style conflict #32704
-   Minor - Update dependency `@wordpress/icons` to ^8.1.0
-   Minor - Added Typescript type declarations. #32615
-   Patch - Standardize lint scripts: add lint:fix

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/experimental/CHANGELOG.md).
