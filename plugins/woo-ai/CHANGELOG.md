# Changelog

## [0.7](https://github.com/woocommerce/woocommerce/releases/tag/0.7) - 2025-04-30 

-   Minor - Adds deprecation notice for Woo AI interactions
-   Minor - Bump node version.
-   Patch - Comment: Fix comment typos across various files.
-   Patch - Update Woo.com references to WooCommerce.com.
-   Minor - Use @automattic/tour-kit@1.1.3
-   Minor - Bump @wordpress/env to 10.14.0 and remove patch for 10.10.0
-   Patch - Bump @wordpress/env to 10.17.0
-   Patch - Bump wireit dependency version to latest.
-   Patch - Fix pnpm version to 9.1.3 to avoid dependency installation issues.
-   Patch - Minor tooling tweaks (zip compression level, composer invocation)
-   Patch - Monorepo: bump and patch wp-env to reduce amount of crashes in CI.
-   Minor - Monorepo: bump pnpm version to 9.15.0
-   Patch - Monorepo: consolidate @babel/* dependencies versions across the monorepo.
-   Patch - Monorepo: consolidate syncpack config around React 17/18 usage.
-   Patch - Monorepo: enable Jest caching.
-   Patch - Monorepo: ensure monorepo packages are linked via workspace-version of the dependencies.
-   Patch - Monorepo: minor tweaks in zip building script (use frozen lock file when installing dependecies).
-   Patch - Monorepo: refresh wireit dependencyOutputs configuration synchronization when installing dependencies.
-   Patch - Monorepo: review deprecated dev-dependecies (take 2)
-   Patch - Monorepo: tweak Webpack loaders paths filtering for better build perfromance.
-   Minor - Remove unused React imports
-   Patch - Update @wordpress-env package to version 9.0.7
-   Major [ **BREAKING CHANGE** ] - Updated declared dependencies to React 18 and Wordpress 6.6
-   Patch - Update pnpm to 9.1.0
-   Patch - Update wireit to 0.14.10
-   Minor - Upgraded Typescript in the monorepo to 5.7.2
-   Minor - Fix typos.
-   Patch - Request valid JSON from the API when generating AI product names

## [0.6](https://github.com/woocommerce/woocommerce/releases/tag/0.6) - 2024-03-29 

-   Patch - Woo AI
-   Patch - Add composer install to changelog script.
-   Patch - Update references to woocommerce.com to now reference woo.com.
-   Patch - Fix Woo AI webpack build configuration.
-   Patch - Only initialize background removal when Jetpack connection is present.
-   Patch - Update / tweak a few more links in docs and comments.
-   Minor - Add React Testing Library and tests to the Woo AI plugin.

## [0.5](https://github.com/woocommerce/woocommerce/releases/tag/0.5) - 2023-10-19 

-   Minor - Adding background removal for legacy product editor images.
-   Minor - Adding feedback snackbar after image background removal
-   Minor - Adding number of images to tracks events for background removal.
-   Minor - Adding spotlight to bring attention to background removal link on Media Library.

## [0.4](https://github.com/woocommerce/woocommerce/releases/tag/0.4) - 2023-09-12 

-   Patch - Add Woo AI Personalization setting and check setting when generating descriptions with AI.
-   Minor - Suggest product categories using AI
-   Minor - [Woo AI] Add a Write with AI button for the short description field in product editor.

## [0.3](https://github.com/woocommerce/woocommerce/releases/tag/0.3) - 2023-08-18 

-   Patch - Fix Woo AI settings page fields persistence bug when disabling the feature.
-   Patch - Woo AI
-   Patch - Update `wp-env` to version 8.2.0.
-   Minor - Adding settings screen for AI centric settings.
-   Minor - Generating short description after long description on product editor.
-   Minor - [Woo AI] Add Store Branding data to product description generation prompt.
-   Minor - Moving text completion hooks into @woocommerce/ai package for reuse.
-   Minor - Updating AI endpoints for product editing features.
-   Minor - Use additional product data (categories, tags, and attributes) when generating product descriptions.
-   Minor - Update pnpm monorepo-wide to 8.6.5
-   Minor - Update pnpm to 8.6.7
-   Minor - Upgrade TypeScript to 5.1.6

## [0.2](https://github.com/woocommerce/woocommerce/releases/tag/0.2) - 2023-06-28 

-   Minor - Adding error handling for a bad token request.
-   Minor - Adding tracks events to indicate view for ai features.
-   Minor - Initial release of WooAI plugin.
-   Minor - Update the product's permalink (slug) when an AI suggested title is selected.

## [0.1.0](https://github.com/woocommerce/woocommerce/releases/tag/0.1.0) - 2023-06-05 

-   Patch - Unhook a duplicate Jetpack Contact Form button hook.
-   Minor - Declare HPOS compatibility.
-   Minor - Truncating product title before sending.

---

[See changelogs for previous versions](https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/changelog.txt).
