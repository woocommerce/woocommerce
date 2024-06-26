# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0](https://www.npmjs.com/package/@woocommerce/block-templates/v/1.1.0) - 2024-04-12 

-   Patch - Corrected build configuration for packages that weren't outputting minified code. [#43716]
-   Minor - Added useLayoutTemplate React hook, to load layout templates via the REST API [#43347]
-   Patch - bump php version in packages/js/*/composer.json [#42020]

## [1.0.0](https://www.npmjs.com/package/@woocommerce/block-templates/v/1.0.0) - 2023-11-27 

-   Minor - New product editor: Disable focus on root blocks, fixing unnecessary tab between fields in new product editor [#41436]
-   Minor - Add conditional visibility support to registered blocks. [#40722]
-   Minor - Initial version of @woocommerce/block-templates package. Adds registerWooBlockType and useWooBlockProps. [#40263]
-   Minor - Use _templateBlockDisableConditions attribute to evaluate disabled attribute [#41307]

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/block-templates/CHANGELOG.md).
