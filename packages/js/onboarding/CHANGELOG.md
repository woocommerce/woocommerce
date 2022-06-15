## [3.1.0](https://www.npmjs.com/package/@woocommerce/onboarding/v/3.1.0) - 2022-06-15

-   Minor - Add ExPlat dependency and product task experiment logic
-   Minor - Add Jetpack Changelogger
-   Patch - Migrate @woocommerce/onboarding to TS
-   Patch - Standardize lint scripts: add lint:fix
-   Patch - Add task_view tracks prop for experimental products #32933
-   Minor - Changed task_view experimental_product key to variant (technically a breaking change but since it was introduced in the same version it is fine) #32944
-   Minor - Removed experimental product hook and instead poll the slot's fill for variant metadata. To be removed when experiment concludes! #33052
-   Minor - Update TaskList types.
-   Minor - Added Typescript type declarations. #32615

---

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/onboarding/CHANGELOG.md).
