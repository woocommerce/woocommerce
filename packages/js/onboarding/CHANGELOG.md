# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
