# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [12.3.0](https://www.npmjs.com/package/@woocommerce/components/v/12.3.0) - 2024-04-26 

-   Minor - Add className prop to Tooltip component #41435 [#41435]
-   Minor - Add popoverProps to DatePicker #41404 [#41404]
-   Minor - Add props to date-range-filter-picker #41372 [#41372]
-   Patch - Corrected build configuration for packages that weren't outputting minified code. [#43716]
-   Patch - Fix "Add a filter" UI issue in Analytics. [#46750]
-   Patch - Fix a few broken links. [#46381]
-   Patch - Fix badge size issue when a number larger than 3 digits is used. [#40624]
-   Patch - Fix invalid date format errors in certain languages [#46932]
-   Patch - Fix sizing of `<SelectControl>`. [#42969]
-   Minor - Fix toggle chart views within Analytics Overview #41329 [#41329]
-   Patch - Fix unable to select the values from the select tree control [#41093]
-   Patch - Set maximum width on Tooltip. [#45358]
-   Minor - Add additionalData prop to MediaUploader component [#42702]
-   Minor - Add className to the MenuItem component [#41653]
-   Minor - Add confetti component and dependency from canvas-confetti package [#46103]
-   Minor - Add support for disabled state in SelectTree [#41307]
-   Minor - Fix non nedded extra SortableItem wrapper [#41550]
-   Minor - Set passed props to the inner div in DisplayState component [#42909]
-   Minor - Bump node version. [#45148]
-   Patch - bump php version in packages/js/*/composer.json [#42020]
-   Minor - Change current page to 1 after selecting a diferent per page amount [#41008]
-   Minor - Improve keyboard interaction of Tree Select Control component. [#41480]
-   Minor - Remove dependency on Jetpack from WooCommerce Shipping & Tax onboarding tasks [#39992]
-   Minor - Remove deprecated use of position for Dropdown component, using popoverProps.placement instead. [#41845]
-   Minor - Remove legacy context API usage from Link component. [#41845]
-   Patch - update references to woocommerce.com to now reference woo.com [#41241]
-   Minor - Update Tree Select Control to allow for searching parent values when `individuallySelectParent` is turned on. [#41559]
-   Patch - Update Woo.com references to WooCommerce.com. [#46259]
-   Minor - Add 'helperText' prop to Tooltip #41251 [#41251]
-   Patch - Fix all lint rule violations for @wordpress/i18n lint rules [#41450]
-   Patch - Fixed some i18n related lint rule violations. [#41450]
-   Patch - Update / tweak a few more links in docs and comments. [#41598]
-   Patch - Update Tree Select Control component to handle accented characters in search. [#41495]

## [12.2.0](https://www.npmjs.com/package/@woocommerce/components/v/12.2.0) - 2023-10-17 

-   Patch - Add class back in for increase specificity of css for dropdown button. [#40494]
-   Patch - Fixed empty component logo color, used generic rather than old pink [#39182]
-   Patch - Fix invalid focus state of the experimental select control [#40519]
-   Patch - Fix select control dropdown menu double scroll and width [#39989]
-   Patch - TreeSelectControl Component
-   Patch - Make eslint emit JSON report for annotating PRs. [#39704]
-   Patch - Upgraded Storybook to 6.5.17-alpha.0 for TypeScript 5 compatibility [#39745]
-   Patch - Add z-index=1 to tour-kit close btn to ensure it's clickable [#40456]
-   Patch - Small condition change in the date time picker to avoid edge case where inputControl is null. [#40642]
-   Minor - Categories dropdown display error #39810 [#39811]
-   Minor - Fix new category name field [#39857]
-   Minor - Select attribute after pressing their names #39456 [#39574]
-   Minor - Add AI wizard business info step for Customize Your Store task [#39979]
-   Minor - Add customize store assembler hub onboarding tour [#39981]
-   Minor - Add ProgressBar component [#39979]
-   Minor - Add tags (or general taxonomy ) block [#39966]
-   Minor - Add Tooltip to each list item when need it [#39770]
-   Minor - An international phone number input with country selection, and mobile phone numbers validation. [#40335]
-   Minor - Image gallery and media uploader now support initial selected images. [#40633]
-   Minor - Refactor Pagination component and split out into multiple re-usable components. Also added a `usePagination` hook. [#39967]
-   Minor - Set button optional in MediaUploader component [#40526]
-   Minor - Update ImageGallery block toolbar, moving some options to an ellipsis dropdown menu. [#39753]
-   Minor - Allow users to select multiple items from the media library while adding images #39741 [#39741]
-   Minor - Update pnpm to 8.6.7 [#39245]
-   Minor - Upgrade TypeScript to 5.1.6 [#39531]
-   Minor - Remove unnecessary use of woocommerce-page selector for DropdownButton styling. [#40218]

## [12.1.0](https://www.npmjs.com/package/@woocommerce/components/v/12.1.0) - 2023-07-13 

-   Patch - Altering styles to correctly target fields within slot fills on product editor. [#36500]
-   Patch - Fix collapsible content heading alignment [#38325]
-   Patch - Fix SelectControl and TreeControl styles. [#36718]
-   Patch - Include CSS for experimental tree control so it renders properly in Storybook. [#36517]
-   Patch - Replace isElevated prop with elevation for tour-kit step component [#38963]
-   Patch - Add ability to focus the first step after opening for tourkit [#38963]
-   Patch - Add an optional "InputProps" to experimental SelectControl component [#36470]
-   Patch - Add onKeyDown and readOnlyWhenClosed options to experimentalSelectControl [#38328]
-   Patch - Opt out of Reset and Help buttons in DateTimePickerControl, as these will be removed in a future @wordpress/components version. [#38480]
-   Patch - Add instructions on how to run the tests when using @woocommerce/components [#38821]
-   Patch - Lint fixes [#38523]
-   Patch - Migrate ellipsis-menu component to TS [#36405]
-   Patch - Migrate Link component to TS [#36285]
-   Patch - Migrate ProductImage component to TS [#36305]
-   Patch - Migrate Rating component to TS [#36301]
-   Patch - Migrate Section component to TS [#36298]
-   Patch - Migrate select control component to TS [#37751]
-   Patch - Migrate Table component to TS [#36370]
-   Patch - Migrate Tag component to TS [#36265]
-   Patch - Update eslint to 8.32.0 across the monorepo. [#36700]
-   Patch - Update TourKit README to correct primaryButton example and formatting. [#37427]
-   Patch - Update webpack config to use @woocommerce/internal-style-build's parser config [#37195]
-   Patch - Correct spelling errors [#37887]
-   Patch - Update positioning of DateTimePickerControl's dropdown. [#38466]
-   Minor - Fix issue where single item can not be cleared and text can not be selected upon click. [#36869]
-   Minor - Fix issue where width of select control dropdown was not correctly calculated when rendering was delayed. [#37295]
-   Minor - Fix SortableItem duplicated id [#36262]
-   Minor - Prevent duplicate registration of core blocks in client [#37350]
-   Minor - Refactor createOrderedChildren [#36707]
-   Minor - Wrap selected items in experimental select control [#38284]
-   Minor - Add 6 basic fields to the product fields registry for use in extensibility within the new Product MVP. [#36392]
-   Minor - Add allowDragging option to ImageGallery to support disabling drag and drop of images. [#38045]
-   Minor - Add callback for the media uploader component when gallery is opened [#38728]
-   Minor - Added LearnMore option as well as made it possible to use this button multiple instances on the page [#36873]
-   Minor - Adding experimental component SlotContext [#36333]
-   Minor - Adding simple DisplayState wrapper and modifying Collapsible component to allow rendering hidden content. [#37305]
-   Minor - Adding the WooProductSectionItem slotfill component. [#36331]
-   Minor - Adding WooProductFieldItem slotfill. [#36315]
-   Minor - Add minFilterQueryLength, individuallySelectParent, and clearOnSelect props. [#36869]
-   Minor - Add new WooProductTabItem component for slot filling tab items. [#36551]
-   Minor - Add product field store and helper functions for rendering fields from config. [#36362]
-   Minor - Add single selection mode to SelectTree [#38140]
-   Minor - Create SelectTree component that uses TreeControl [#37319]
-   Minor - Expose registerBlocks as registerRichTextEditorBlocks from the rich text editor package [#38982]
-   Minor - Fix dependency versions [#37023]
-   Minor - Make DateTimePickerControl a ForwardedRef component" [#38306]
-   Minor - TreeControl: Fix a bug where items with children were not selected in single mode and fix a bug where navigation between items with tab was not working properly. [#38079]
-   Minor - Add deprecated message to packages moved to product-editor package. [#36815]
-   Minor - Add deprecated message to product slot fill components [#36830]
-   Minor - Apply wccom experimental select control changes [#36521]
-   Minor - Export TreeSelectControl component and add additional props: onInputChange, alwaysShowPlaceholder, includeParent. [#36932]
-   Minor - Show comma separated list in ready only mode of select tree control [#38052]
-   Minor - Updated AdvancedFilter to use createInterpolateElement instead of interpolateComponents. [#37967]
-   Minor - Update select tree control dropdown menu for custom slot fill support for display within Modals [#37574]
-   Minor - Updating the product editor fill components to support multiple targets. [#36592]
-   Minor - Updating WooProductFieldItem to uniquely generate IDs with different sections. [#36646]
-   Minor - Add a11y support for the Tree component [#36459]
-   Minor - Add custom rendering logic to the item label [#36476]
-   Minor - Adjust eslintrc for changes to eslint plugin. [#36988]
-   Minor - Create tree-control component [#36432]
-   Minor - Sync @wordpress package versions via syncpack. [#37034]
-   Minor - Update pnpm monorepo-wide to 8.6.5 [#38990]
-   Minor - Update pnpm to version 8. [#37915]
-   Minor - Improve a11y support to collapsible content component [#37760]
-   Minor - Small tweak to update reference to currencyContext component. [#36959]
-   Minor - Use BaseControl in the SelectTree label [#38261]

## [12.0.0](https://www.npmjs.com/package/@woocommerce/components/v/12.0.0) - 2022-12-28 

-   Patch - Add name to exported popover slot used to display SelectControl Menu, so it is only used for SelectControl menus. [#36124]
-   Patch - Close DateTimePickerControl's dropdown when blurring from input. [#36124]
-   Patch - DateTimePickerControl's onChange now only fires when there is an actual change to the datetime. [#36124]
-   Patch - Fix DateTimePickerControl's popover styling when slot-fill is used. [#36124]
-   Patch - Fixed DatePicker to work in WordPress 6.1 when currentDate is set to a moment instance. [#36124]
-   Patch - Fix pagination label text from uppercase to normal and font styles [#36124]
-   Patch - Include react-dates styles (no longer in WP 6.1+). [#36124]
-   Patch - Set initial values prop from reset form function as optional [#36124]
-   Patch - Add aria-label for simple select dropdown [#36124]
-   Patch - Add async filtering support to the `__experimentalSelectControl` component [#36124]
-   Patch - Add experimental open menu when user focus the select control input element [#36124]
-   Patch - Updating downshift to 6.1.12. [#36124]
-   Patch - Migrate search component to TS [#36124]
-   Patch - Updated image gallery toolbar position and tooltips. [#36124]
-   Patch - Update variable name within useFormContext. [#36124]
-   Patch - Align the field height across the whole form [#36124]
-   Patch - Fade the value selection field in the Attributes modal when no attribute is added [#36124]
-   Patch - Update font size and spacing in the tooltip component [#36124]
-   Minor - Set editor mode on initialization to prevent initial text editor focus [#36124]
-   Minor - Add className prop to ListItem. [#36124]
-   Minor - Add className prop to Sortable [#36124]
-   Minor - Added ability to force time when DateTimePickerControl is date-only (timeForDateOnly prop). [#36124]
-   Minor - Add experimental ConditionalWrapper component [#36124]
-   Minor - Adding isHidden option for primary button in TourKit component. [#36124]
-   Minor - Add support for custom suffix prop on SelectControl. [#36124]
-   Minor - Make Table component accept className prop. [#36124]
-   Minor - Move classname down in SelectControl Menu so it is on the actual Menu element. [#36124]
-   Minor - Allow the user to select multiple images in the Media Library [#36124]
-   Minor - Move file picker by clicking card into the MediaUploader component [#36124]
-   Minor - Fix up initial block selection in RichTextEditor and add media blocks [#36124]
-   Minor - Add noDataLabel property into table.js component to allow No Data label customization. [#36124]
-    - Switch DateTimePickerControl formatting to PHP style, for WP compatibility. [#36124]

## [11.1.0](https://www.npmjs.com/package/@woocommerce/components/v/11.1.0) - 2022-10-24 

-   Minor - Allow passing of additional props to form inputs [#35160]

## [11.0.0](https://www.npmjs.com/package/@woocommerce/components/v/11.0.0) - 2022-10-20 

-   Patch - Export StepperProps for external usage [#35140]
-   Patch - Fixed the initial setting of DateTimePickerControl's input field. [#35140]
-   Patch - Fix EnrichedLabel Storybook story styles so they don't affect other stories. [#35140]
-   Patch - Fixes DateTimePickerControl's debounce handling to work even if onChange prop changes. [#35140]
-   Patch - Fix issue with form onChange handler, passing outdated values. [#35140]
-   Patch - Update tag component styling [#35140]
-   Patch - Add missing type definitions and add babel config for tests [#35140]
-   Patch - Merging trunk with local [#35140]
-   Patch - Removed unfinished and unused SplitDropdown component. [#35140]
-   Patch - Assume ambiguous dates passed into DateTimePickerControl are UTC. [#35140]
-   Patch - Remove default selected sortable item. [#35140]
-   Minor - Fix Enriched-label styles
-   Minor - Fix initially selected items in SelectControl component [#35140]
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
-   Minor - Update text input placement in SelectControl [#35140]
-   Minor - Add component EnrichedLabel #34214 [#35140]
-   Minor - Add new shippping class modal to a shipping class section in product page [#35140]
-   Minor - Adjust build/test scripts to remove -- -- that was required for pnpm 6. [#35140]
-   Minor - Fix node and pnpm versions via engines [#35140]
-   Minor - Update Plugin installer component to TS [#35140]
-   Minor - Update pnpm version constraint to 7.13.3 to avoid auto-install-peers issues [#35140]
-   Minor - Fix DateTimePickerControl's onChange date arg to only be a string (TypeScript). [#35140]
-   Minor - Improve experimental SelectControl accessibility [#35140]
-   Minor - Improve Sortable component acessibility [#35140]
-   Major [ **BREAKING CHANGE** ] - Create new experimental SelectControl component [#35140]

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
