# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
