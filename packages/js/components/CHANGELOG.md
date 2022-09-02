# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [10.3.0](https://www.npmjs.com/package/@woocommerce/components/v/10.3.0) - 2022-08-12 

-   Patch - Added in missing TS definitions in package.json [#34279]
-   Patch - fixed button rendering for 1 step tour which was not showing completion button due to bug in logic [#34279]
-   Minor - Adding basic CollapsibleContent component. [#34279]
-   Minor - Add the use of a context provider for the Form component. #34082 [#34279]
-   Minor - Update types for Form component and allow Form state to be reset. [#34279]
-   Minor - Removed Step 1 of 1 step description for 1 step tours [#34279]

## [10.2.1](https://www.npmjs.com/package/@woocommerce/components/v/10.2.1) - 2022-07-19 

-   Patch - Fix missing text domain

## [10.2.0](https://www.npmjs.com/package/@woocommerce/components/v/10.2.0) - 2022-07-08 

-   Minor - Add step name to tour kit step type and export CloseHandler type to be reused elsewhere
-   Minor - Tree Select Control Component
-   Minor - Updated @automattic/tour-kit to 1.1.1 which has live resize functionality
-   Minor - Plugins component skip button is now optional
-   Minor - Remove PHP and Composer dependencies for packaged JS packages
-   Patch - Tweak tour kit gap between content and controls

## [10.1.0](https://www.npmjs.com/package/@woocommerce/components/v/10.1.0) - 2022-06-09 

-   Minor - Add tour kit component
-   Minor - Update dependency `memoize-one` to ^6.0.0. #32936
-   Minor - Update dependency `react-dates` to ^21.8.0. #32883
-   Minor - Add Jetpack Changelogger
-   Minor - Update dependency `@wordpress/hooks` to ^3.5.0
-   Minor - Update dependency `@wordpress/icons` to ^8.1.0
-   Minor - Add `className` prop for Pill component. #32605
-   Patch - Removed unused react-router-dom dependency #33156
-   Patch - Standardize lint scripts: add lint:fix
-   Patch - Fix documentation for `TableCard` component
-   Patch - Update `StepperProps` prop types. #32712

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/components/CHANGELOG.md).
