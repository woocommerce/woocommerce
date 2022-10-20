# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [11.0.0](https://www.npmjs.com/package/@woocommerce/components/v/11.0.0) - 2022-10-20 

-   Patch - Export StepperProps for external usage [#35140]
-   Patch - Fixed the initial setting of DateTimePickerControl's input field. [#35140]
-   Minor - Fix Enriched-label styles - #34382 [#35140]
-   Patch - Fix EnrichedLabel Storybook story styles so they don't affect other stories. [#35140]
-   Patch - Fixes DateTimePickerControl's debounce handling to work even if onChange prop changes. [#35140]
-   Minor - Fix initially selected items in SelectControl component [#35140]
-   Patch - Fix issue with form onChange handler, passing outdated values. [#35140]
-   Minor - Add date-only mode to DateTimePickerControl. [#35140]
-   Minor - Add disabled option to the Select Control input component and alter the onInputChange callback [#35140]
-   Minor - Add form input name dot notation name="product.dimensions.width" [#35140]
-   Minor - Add FormSection component [#35140]
-   Minor - Add ImageGallery component [#35140]
-   Minor - Adding datetimepicker component. [#35140]
-   Minor - Adding on-click toolbar to image gallery component items. [#35140]
-   Minor - Add label prop to rich text editor [#35140]
-   Minor - Add MediaUploader component [#35140]
-   Minor - Add rich text editor component [#35140]
-   Minor - Add SortableList component [#35140]
-   Minor - Allow external tags in SelectControl component [#35140]
-   Major [ **BREAKING CHANGE** ] - Create new experimental SelectControl component [#35140]
-   Minor - Export ImportProps type. Add DateTimePickerControl to Form stories and tests. [#35140]
-   Minor - Images Product management [#35140]
-   Minor - Remove EnrichedLabel component in favor of Tooltip component [#35140]
-   Minor - Update resetForm arguments, adding changed fields, touched fields and errors. [#35140]
-   Minor - [PM Components] Create SplitDropdown component. #34180 [#35140]
-   Minor - Add label, placeholder, and help props to DateTimePickerControl. [#35140]
-   Minor - Adds setValues support to FormContext [#35140]
-   Minor - Add support in SelectControl for using the popover slot for the popover. [#35140]
-   Minor - Update experimental SelectControl compoment to expose a couple extra combobox functions from Downshift. [#35140]
-   Minor - Update experimental SelectControl compoment to expose combobox functions from Downshift and provide additional options. [#35140]
-   Patch - Update tag component styling [#35140]
-   Minor - Update text input placement in SelectControl [#35140]
-   Minor - Add component EnrichedLabel #34214 [#35140]
-   Patch - Add missing type definitions and add babel config for tests [#35140]
-   Minor - Add new shippping class modal to a shipping class section in product page [#35140]
-   Minor - Adjust build/test scripts to remove -- -- that was required for pnpm 6. [#35140]
-   Minor - Fix node and pnpm versions via engines [#35140]
-   Patch - Merging trunk with local [#35140]
-   Patch - Removed unfinished and unused SplitDropdown component. [#35140]
-   Minor - Update Plugin installer component to TS [#35140]
-   Minor - Update pnpm version constraint to 7.13.3 to avoid auto-install-peers issues [#35140]
-   Patch - Assume ambiguous dates passed into DateTimePickerControl are UTC. [#35140]
-   Minor - Fix DateTimePickerControl's onChange date arg to only be a string (TypeScript). [#35140]
-   Patch - Remove default selected sortable item. [#35140]
-   Minor - Improve experimental SelectControl accessibility [#35140]
-   Minor - Improve Sortable component acessibility [#35140]

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
