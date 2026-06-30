# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0](https://www.npmjs.com/package/@woocommerce/remote-logging/v/2.0.0) - 2026-06-11 

-   Major - Update @wordpress/* dependencies to WordPress 6.8 minimum. [#64114]
-   Major - Updated declared dependencies to React 18 and WordPress 6.6. [#53531]
-   Minor - Bump jest package dependency to 29.5.x. [#60324]
-   Minor - Monorepo: bump pnpm version to 9.15.0. [#54189]
-   Minor - Upgraded TypeScript in the monorepo to 5.7.2. [#53165]
-   Patch - Bump wireit dependency version to latest. [#57299]
-   Patch - CI: leverage composer packages cache in lint monorepo job. [#52054]
-   Patch - Clean up CI job config options; remove unused cascading keys. [#55863]
-   Patch - Monorepo: build RAM usage optimization. [#59046]
-   Patch - Monorepo: consolidate @babel/* dependencies versions across the monorepo. [#56575]
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`. [#58941]
-   Patch - Monorepo: consolidate syncpack config around React 17/18 usage. [#52022]
-   Patch - Monorepo: consolidate TypeScript config files and JS test directories naming. [#52191]
-   Patch - Monorepo: drop the unused `concurrently` package from dependencies. [#58765]
-   Patch - Monorepo: refresh wireit dependencyOutputs configuration synchronization when installing dependencies. [#55095]
-   Patch - Move the CommonJS build to prepack so day-to-day development only builds the ESM output. [#64876]
-   Patch - Move TypeScript type-checking from the build to a new `lint:lang:types` script. Builds now emit types and JS without type-checking. [#65168]
-   Patch - Replaced patched `@wordpress/data` types with opt-in internal package types. [#63483]
-   Patch - Replaced wireit + tsc package build pipeline with a per-package esbuild script. [#65210]
-   Patch - Update wireit to 0.14.10. [#54996]

## [1.0.0](https://www.npmjs.com/package/@woocommerce/remote-logging/v/1.0.0) - 2024-09-09 

-   Patch - Fix wc asset url check [#50701]
-   Minor - Add JS remote logging package [#49702]
-   Patch - Track frequency of unhandled JS errors with MC Stats [#50155]
-   Minor - Add query params sanitisation [#51046]
-   Patch - Add request_uri prop to remote logging data [#50671]
-   Patch - Comment: Fix some comment typos. [#50993]
-   Patch - Update README.md to document the filters specs & usage [#51010]
-   Patch - Update remote logger to check dev env and whether logging is enabled [#50134]
-   Patch - Fix pnpm version to 9.1.3 to avoid dependency installation issues. [#50828]
-   Patch - Tweak logic for adding remote logging tool in beta tester [#50425]

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/remote-logging/CHANGELOG.md).
