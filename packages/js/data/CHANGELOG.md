# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.1.0](https://www.npmjs.com/package/@woocommerce/packages/js/data/v/4.1.0) - 2022-07-08 

-   Minor - Fix 'Cannot read properties of undefined' error when clicking Export button on Analytic pages.
-   Minor - Add CRUD data store utilities
-   Minor - Add product deletion via datastore API #33285
-   Minor - Add product shipping class data store. #33765
-   Patch - Fix product type
-   Patch - Migrate @woocommerce/data user and use-select-with-refresh to TS
-   Patch - Migrate item store to TS
-   Minor - Migrate onboarding data store to TS
-   Minor - Remove PHP and Composer dependencies for packaged JS packages

## [4.0.0](https://www.npmjs.com/package/@woocommerce/packages/js/data/v/4.0.0) - 2022-06-14 

-   Major [ **BREAKING CHANGE** ] - Remove `PaymentMethodsState` type. Use `Plugin` instead. #32683
-   Minor - Add create product actions in products data store #33278
-   Minor - Add new orders data store, for retrieving orders data. #33063
-   Minor - Add update product actions to product data store #33282
-   Minor - Add Jetpack Changelogger
-   Minor - Added TypeScript options selectors and action in onboarding store for keeping the completed task list. #32158
-   Minor - Add product data store for retrieving product list.
-   Minor - Update dependency `@wordpress/hooks` to ^3.5.0
-   Minor - Add `is_offline` attribute for `Plugin` type. #32467
-   Minor - Added Typescript type declarations. #32615
-   Minor - Update type definitions. #32683, #32695, #32698, #32712
-   Minor - Make `isResolving` param `args` optional.
-   Minor - Update `Plugin` type to reflect the latest changes.
-   Minor - Maps "raw" payment `ActionDispatchers` to the registered actions.
-   Minor - Export `getTaskListsByIds`, `getTaskLists`, `getTaskList`, `getFreeExtensions` onboarding selector types
-   Minor - Update `TaskType` & `TaskListType` types
-   Minor - Export `InstallPluginsResponse` type
-   Minor - Convert `use-user-preferences.js` to TS. #32695
-   Minor - Added PaymentGateway type to exports #32697
-   Minor - Add `@types/wordpress__compose`, `@types/wordpress__data`, `redux` types and fix related type errors. #32735
-   Minor - Fix issue in `onboarding` data package for the unhide and hide success actions. #32926
-   Patch - Migrate options store to TS
-   Patch - Migrate woo.data export & import store to TS
-   Patch - Standardize lint scripts: add lint:fix
-   Patch - Update @woocomerce/data client api error types. #32939

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/data/CHANGELOG.md).
