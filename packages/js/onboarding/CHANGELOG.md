# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0](https://www.npmjs.com/package/@woocommerce/onboarding/v/4.0.0) - 2026-06-11 

-   Major - Update @wordpress/* dependencies to WordPress 6.8 minimum. [#64114]
-   Major - Updated declared dependencies to React 18 and WordPress 6.6. [#53531]
-   Minor - Add a popover to WooPayments to present all possible payment methods. [#54404]
-   Minor - Add store location mismatch indicator to the Settings -> Payments NOX page. [#54638]
-   Minor - Branding updates to woocommerce payments welcome and payments task pages. [#54571]
-   Minor - Bump jest package dependency to 29.5.x. [#60324]
-   Minor - Fix the name of the Manawatu-Whanganui region. [#54231]
-   Minor - Improve build time for onboarding by using webpack filesystem cache. [#64082]
-   Minor - Improved the WooPaymentsMethodsLogos component to support provided breakpoints and number of methods shown for those. [#53900]
-   Minor - ISO Code update for Odisha state (India) [ISO 3166-2:IN](https://en.wikipedia.org/wiki/ISO_3166-2:IN) (23 November 2023). [#53341]
-   Minor - Monorepo: bump pnpm version to 9.15.0. [#54189]
-   Minor - Remove unused React imports. [#55554]
-   Minor - Replace Woopayments and WooPay logos with the new rebranded logos. [#54023]
-   Minor - Update storybook file format in support with Storybook 7 story indexer. [#51168]
-   Minor - Update the country Turkey to Türkiye. [#58436]
-   Minor - Update the list of supported payment methods in WooPayments to add GrabPay. [#57436]
-   Minor - Update the test step title when polling is happening to communicate progress to the merchant. [#58968]
-   Minor - Update tooltips on the Payments Settings page to work on click instead of hover. [#57856]
-   Minor - Upgraded TypeScript in the monorepo to 5.7.2. [#53165]
-   Patch - Add Escape key support to close the WooPaymentsMethodsLogos popover. [#62841]
-   Patch - Bump wireit dependency version to latest. [#57299]
-   Patch - CI: leverage composer packages cache in lint monorepo job. [#52054]
-   Patch - Clean up CI job config options; remove unused cascading keys. [#55863]
-   Patch - Enhance Settings -> Payments NOX pages styling for mobile and tablet screens. [#54566]
-   Patch - Ensure Lakshadweep is spelt correctly. [#59295]
-   Patch - Fix a bug introducing global popover styling. [#54723]
-   Patch - Fix global popover CSS styling bug. [#54828]
-   Patch - Fix pnpm version to 9.1.3 to avoid dependency installation issues. [#50828]
-   Patch - Fix react-18-upgrade TODOs (@ts-expect-error) in WC Admin. [#55478]
-   Patch - Fix slotfill types. [#54711]
-   Patch - Migrate from React RC types to direct React Props types with jscodeshift codemod. [#56594]
-   Patch - Monorepo: build RAM usage optimization. [#59001]
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
-   Patch - Move TypeScript type-checking from the build to a new `lint:lang:types` script. Builds now emit types and JS without type-checking. [#65168]
-   Patch - Replaced patched `@wordpress/data` types with opt-in internal package types. [#63483]
-   Patch - Replaced wireit + tsc package build pipeline with a per-package esbuild script. [#65210]
-   Patch - Update wireit to 0.14.10. [#54996]

## [3.6.0](https://www.npmjs.com/package/@woocommerce/onboarding/v/3.6.0) - 2024-07-29 

-   Minor - Add a new shared component to display logos of payment methods supported by WooPayments. [#49300]
-   Minor - Added Task Referral system for wc-admin onboarding tasks. [#47654]
-   Patch - Update events that should trigger the test job(s) [#47612]
-   Patch - Update pnpm to 9.1.0 [#47385]
-   Minor - Fix typo in findCountryOption test [#48648]

## [3.5.0](https://www.npmjs.com/package/@woocommerce/onboarding/v/3.5.0) - 2024-04-26 

-   Minor - Branding rollout - change WooCommerce Payments to WooPayments [#39188]
-   Patch - Corrected build configuration for packages that weren't outputting minified code. [#43716]
-   Patch - Fix minor layout shift in the core profiler. [#39898]
-   Patch - Fix styling issues with WooPayments banner on mobile version. [#46647]
-   Minor - Remove accent from country labels when comparing against geo detected country [#39110]
-   Minor - Added shouldLoop prop for the Loader component to determine if looping should happen [#40829]
-   Minor - Bump node version. [#45148]
-   Patch - bump php version in packages/js/*/composer.json [#42020]
-   Patch - Remove BNPL (Klarna) additional payment method from WooPayments welcome page, update payment method icons [#46523]
-   Minor - Remove references to Sofort in the Onboarding. [#40745]
-   Patch - Remove ToS acceptance where unnecessary [#46003]
-   Patch - update references to woocommerce.com to now reference woo.com [#41241]
-   Minor - Update WCPay banners for WooPay in eligible countries. [#39596]
-   Patch - Update Woo.com references to WooCommerce.com. [#46259]
-   Patch - Make eslint emit JSON report for annotating PRs. [#39704]
-   Minor - Refactored core profiler loader to be more generalizable and moved to @woocommerce/onboarding [#39735]
-   Minor - Update pnpm monorepo-wide to 8.6.5 [#38990]
-   Minor - Update pnpm to 8.6.7 [#39245]
-   Minor - Upgrade TypeScript to 5.1.6 [#39531]
-   Minor - Update the CYS task API loader smoother [#41279]

## [3.4.0](https://www.npmjs.com/package/@woocommerce/onboarding/v/3.4.0) - 2023-06-20 

-   Minor - Added getCountry utility for splitting colon delimited country:state strings [#38536]
-   Minor - Replace use of interpolateComponents with createInterpolateElement. [#38536]
-   Minor - Fix lint issues [#38536]
-   Minor - Moved geolocation country matching functions to @woocommerce/onboarding [#38536]
-   Minor - Sync @wordpress package versions via syncpack. [#38536]
-   Minor - Update pnpm to version 8. [#38536]
-   Patch - Update webpack config to use @woocommerce/internal-style-build's parser config [#38536]
-   Patch - Fix a word case typo. [#38536]

## [3.3.0](https://www.npmjs.com/package/@woocommerce/onboarding/v/3.3.0) - 2023-02-14 

-   Patch - Added in missing TS definitions in package.json [#36701]
-   Patch - Fix wcpay benefits padding [#36701]
-   Patch - Cleanup product task experiment [#36701]
-   Patch - Update eslint to 8.32.0 across the monorepo. [#36701]
-   Minor - Add WooOnboardingTaskListHeader component [#36701]
-   Minor - Adjust build/test scripts to remove -- -- that was required for pnpm 6. [#36701]
-   Minor - Fix node and pnpm versions via engines [#36701]
-   Minor - Match TypeScript version with syncpack [#36701]
-   Minor - Update pnpm version constraint to 7.13.3 to avoid auto-install-peers issues [#36701]

## [3.2.0](https://www.npmjs.com/package/@woocommerce/onboarding/v/3.2.0) - 2022-07-08 

-   Minor - Add WCPayBanner & WCPayBenefits components
-   Minor - Remove PHP and Composer dependencies for packaged JS packages

## [3.1.0](https://www.npmjs.com/package/@woocommerce/onboarding/v/3.1.0) - 2022-06-15 

-   Minor - Add ExPlat dependency and product task experiment logic
-   Minor - Add Jetpack Changelogger
-   Minor - Changed task_view experimental_product key to variant (technically a breaking change but since it was introduced in the same version it is fine) #32944
-   Minor - Removed experimental product hook and instead poll the slot's fill for variant metadata. To be removed when experiment concludes! #33052
-   Minor - Update TaskList types.
-   Minor - Added Typescript type declarations. #32615
-   Patch - Migrate @woocommerce/onboarding to TS
-   Patch - Standardize lint scripts: add lint:fix
-   Patch - Add task_view tracks prop for experimental products #32933

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/onboarding/CHANGELOG.md).
