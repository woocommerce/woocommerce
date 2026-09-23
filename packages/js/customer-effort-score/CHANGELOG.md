# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0](https://www.npmjs.com/package/@woocommerce/customer-effort-score/v/4.0.0) - 2026-06-11 

-   Major - Update @wordpress/* dependencies to WordPress 6.8 minimum. [#64114]
-   Major - Updated declared dependencies to React 18 and WordPress 6.6. [#53531]
-   Minor - Bump jest package dependency to 29.5.x. [#60324]
-   Minor - Fix typos in inline documentation. [#48640]
-   Minor - Fix: Responsiveness of CES feedback form. [#52938]
-   Minor - Improve build time for customer-effort-score by using webpack filesystem cache. [#64082]
-   Minor - Monorepo: bump pnpm version to 9.15.0. [#54189]
-   Minor - Update store registration to use createReduxStore and fix type errors. [#55584]
-   Minor - Upgraded TypeScript in the monorepo to 5.7.2. [#53165]
-   Patch - A fix a bug where users need to click Give feedback twice. [#52556]
-   Patch - Added `__nextHasNoMarginBottom` prop to various WordPress components and updated styling to support WordPress 6.7+ margin style changes. [#56257]
-   Patch - Bump wireit dependency version to latest. [#57299]
-   Patch - CI: leverage composer packages cache in lint monorepo job. [#52054]
-   Patch - Clean up CI job config options; remove unused cascading keys. [#55863]
-   Patch - Drop the default pencil icon from CES snackbars so they render without cross-platform glyph rendering issues and alignment quirks; callers can still pass an explicit icon when needed. [#64357]
-   Patch - Fix "description" prop ignored in CustomerEffortScoreModalContainer. [#55747]
-   Patch - Fix broken CES style for WP 6.7. [#52499]
-   Patch - Fix CES options being on two rows when scrollbar is visible. [#55923]
-   Patch - Fix pnpm version to 9.1.3 to avoid dependency installation issues. [#50828]
-   Patch - Fix unstable `useSelect()` return value in feedback button. [#63554]
-   Patch - Migrate from React RC types to direct React Props types with jscodeshift codemod. [#56594]
-   Patch - Monorepo: address circular dependencies surfaced by SWC TDZ. [#64797]
-   Patch - Monorepo: build RAM usage optimization. [#58861]
-   Patch - Monorepo: complete migration from `classnames` package to `clsx`. [#58699]
-   Patch - Monorepo: consolidate @babel/* dependencies versions across the monorepo. [#56575]
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
-   Patch - Move tracking option API call to listener function with caching. [#48306]
-   Patch - Move TypeScript type-checking from the build to a new `lint:lang:types` script. Builds now emit types and JS without type-checking. [#65168]
-   Patch - Remove propTypes and defaultProps from react components in TypeScript. [#56733]
-   Patch - Replaced patched `@wordpress/data` types with opt-in internal package types. [#63483]
-   Patch - Replaced wireit + tsc package build pipeline with a per-package esbuild script. [#65210]
-   Patch - Stabilize JS dependency updates. [#52815]
-   Patch - Update @wordpress/* peerDependencies from dist-tags to semver ranges for pnpm 10 compatibility. [#63964]
-   Patch - Update import of OPTIONS_STORE_NAME to optionsStore. [#55476]
-   Patch - Update wireit to 0.14.10. [#54996]

## [3.0.0](https://www.npmjs.com/package/@woocommerce/customer-effort-score/v/3.0.0) - 2024-06-11 

-   Patch - Added in missing TS definitions in package.json [#34154]
-   Minor - Add extraFields and showDescription props [#38643]
-   Patch - Corrected build configuration for packages that weren't outputting minified code. [#43716]
-   Patch - Fixing the onsubmit_label prop from SHOW_CES_MODAL action incorreclty named as onSubmitLabel [#39055]
-   Patch - Fix modal border radius and content scrolling [#38325]
-   Minor - Fix modal styles [#38775]
-   Patch - Fix styling issue with new Wordpress version. [#35602]
-   Minor - Show feedback prompt only once #43164 [#43164]
-   Minor - Add a function to help decide if comments section should be shown [#36484]
-   Minor - Add description and noticeLabel props to customer feedback components. [#35728]
-   Minor - Add FeedbackModal and ProductMVPFeedbackModal components [#36532]
-   Minor - Add props to allow passing a classname to the feedback modal [#38592]
-   Minor - Add value props to CustomerFeedbackSimple component [#46103]
-   Minor - Bump node version. [#45148]
-   Patch - bump php version in packages/js/*/composer.json [#42020]
-   Minor - Update CustomerEffortScore tracks to add callback for when Modal is dismissed. [#35761]
-   Minor - Update text for options to match questions, and provide custom options prop. [#35652]
-   Major [ **BREAKING CHANGE** ] - Updating to accept two questions to display in CES modal. [#35680]
-   Minor - Add additional components to package. [#37112]
-   Minor - Add CES data store to @woocommerce/customer-effort-score [#37252]
-   Minor - Add `onCancel` callback #43005 [#43005]
-   Minor - Adjust build/test scripts to remove -- -- that was required for pnpm 6. [#34661]
-   Minor - Fix lint issues [#36988]
-   Minor - Fix node and pnpm versions via engines [#34773]
-   Patch - Make eslint emit JSON report for annotating PRs. [#39704]
-   Minor - Match TypeScript version with syncpack [#34787]
-   Patch - Merging trunk into local [#34322]
-   Minor - Move additional components to @woocommerce/customer-effort-score. [#37316]
-   Minor - Move ProductMVPFeedbackModal to @woocommerce/product-editor [#37131]
-   Patch - Remove unused constant. [#38599]
-   Minor - Sync @wordpress package versions via syncpack. [#37034]
-   Patch - Update @wordpress/data to ^6.15.0 [#34428]
-   Patch - Update eslint to 8.32.0 across the monorepo. [#36700]
-   Patch - Update events that should trigger the test job(s) [#47612]
-   Minor - Update pnpm monorepo-wide to 8.6.5 [#38990]
-   Minor - Update pnpm to 8.6.7 [#39245]
-   Patch - Update pnpm to 9.1.0 [#47385]
-   Patch - Update pnpm to version 8 [#37915]
-   Minor - Update pnpm version constraint to 7.13.3 to avoid auto-install-peers issues [#35007]
-   Patch - Update webpack config to use @woocommerce/internal-style-build's parser config [#37195]
-   Minor - Upgrade TypeScript to 5.1.6 [#39531]
-   Minor - Set secondQuestion and title as optional [#36270]
-   Minor - Adding support for tracksProps to CES modal container. [#37720]

## [2.2.0](https://www.npmjs.com/package/@woocommerce/customer-effort-score/v/2.2.0) - 2022-07-08 

-   Minor - Remove PHP and Composer dependencies for packaged JS packages

## [2.1.0](https://www.npmjs.com/package/@woocommerce/customer-effort-score/v/2.1.0) - 2022-06-14 

-   Minor - Add new simple customer feedback component for inline CES feedback. #32538
-   Minor - Add Jetpack Changelogger
-   Minor - Add TypeScript type support as part of the build process. #32538
-   Patch - Migrate @woocommerce/customer-effort-score to TS
-   Patch - Standardize lint scripts: add lint:fix

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/customer-effort-score/CHANGELOG.md).
