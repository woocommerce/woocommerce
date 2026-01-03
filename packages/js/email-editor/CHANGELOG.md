# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.5.0) - 2025-12-15 

-   Patch - Handle missing layout settings in the editor settings [#61471]
-   Minor - Add category tabs navigation to email template selection modal. The TemplateCategory type is now a string to support dynamic categories loaded from block patterns. [#62441]
-   Patch - Add optional createCoupon URL to EmailEditorUrls type for coupon creation integration
-   Patch - Improve UX for tax-inclusive pricing configuration by adding validation notice and clearer setting description when base tax rate is not configured. [#61471]

## [1.4.3](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.4.3) - 2025-12-11 

-   Patch - Compatibility update for Gutenberg 22.0. useEmailCss now returns styles correctly with Gutenberg 22.0+. [#61964]

## [1.4.2](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.4.2) - 2025-12-04 

-   Patch - Handle missing layout settings in the editor settings [#62237]

## [1.4.1](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.4.1) - 2025-11-25 

-   Patch - Remove unnecessary hook for media library [#62127]

## [1.4.0](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.4.0) - 2025-11-12 

-   Patch - Prevent crashes with Gutenberg 22.0 [#61925]

## [1.3.0](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.3.0) - 2025-11-05 

-   Minor - Add setEmailPostType action and export email editor hooks and utilities for template previews and styling [#61804]

## [1.2.1](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.2.1) - 2025-11-04 

-   Patch - Export the personalization tags RichTextWithButton component and other email editor methods. [#61748]

## [1.2.0](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.2.0) - 2025-10-31 

-   Patch - Ensure the Command palette is functional in WordPress 6.9 beta and above. [#61672]
-   Minor - Add 'woocommerce-email-editor' plugin area [#61666]

## [1.1.3](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.1.3) - 2025-10-27 

-   Patch - Fix for email preview to fit into the container in the preview column [#61442]
-   Patch - Refactor personalization tags fetching to use core entities [#61467]
-   Patch - Add support for a custom back button component [#61535]

## [1.1.2](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.1.2) - 2025-10-09 

-   Patch - Export hook isEmailEditor from the package. [#60941]

## [1.1.1](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.1.1) - 2025-09-26 

-   Patch - Allow passing editor configuration to ExperimentalEmailEditor via props [#60974]

## [1.1.0](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.1.0) - 2025-09-09 

-   Minor - Export `SendPreviewEmail` component and `createStore` [#60796]
-   Minor - Bump jest package dependency to 29.5.x [#60324]
-   Minor - Add support for contentRef property to the experimental editor component [#60821]
-   Minor - Refactor usage of hooks and global Gutenberg functions to support preserving and restoring the original state [#60741]
-   Patch - Display warning message when user clicks on "Preview in new tab" without saving the edited changes [#60307]

## [1.0.4](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.0.4) - 2025-08-21 

-   Patch - Filter unnecessary stylesheets from Editor iframe [#60354]
-   Patch - Fix site logo not rendered properly. In the email editor if the site logo was in a group block the right alignment didn't work. [#60290]
-   Patch - Ensure the email editor obtains and utilizes the site theme styles as part of its default values. [#60465]

## [1.0.3](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.0.3) - 2025-08-11 

-   Patch - Fixed a possible infinite loading of templates when editing emails. [#60196]

## [1.0.2](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.0.2) - 2025-08-01 

-   Patch - Fix backward compatibility when personalization tag post types are not set. [#60134]

## [1.0.1](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.0.1) - 2025-07-31 

-   Patch - Email Editor: show “Send” button when editing a new (unsaved) email draft. [#59931]
-   Patch - Email Editor: validation now correctly runs when both the email body and its template are modified, preventing silent skips of template-only edits. [#59903]
-   Patch - Fix block theme styles interference with the email editor by removing all unsupported block styles. [#60024]
-   Patch - Fix crash of the post content block in the email editor [#59791]
-   Patch - Fix errors with using block theme styles in the email editor. [#59757]
-   Patch - Register a custom variation for the site logo block to set the email editor required attributes. [#59624]
-   Patch - Add filtering email patterns by the email postType. [#60015]
-   Patch - Add filtering personalization tags by the email post type. [#60072]
-   Patch - Use ToolsPanel component instead of PanelBody component [#59632]

## [1.0.0](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.0.0) - 2025-07-09 

-   Patch - Add fallback for Navigator component to the email editor [#58083]
-   Patch - Fix an error with the Email Editor not loading Global Styles for non-admin WordPress users and ensure permissions are correctly checked when required. [#56261]
-   Patch - Fix crash in the email editor when a third-party sidebar component throws an error [#58459]
-   Patch - Fix WordPress 6.8 compatibility [#56820]
-   Patch - Resolve event handler stacking in `useContentValidation()`. [#58247]
-   Patch - Add a warning about border units to the email editor. [#58169]
-   Patch - Remove "Swap template" from email editor [#56829]
-   Patch - Rename the details panel in the sidebar to settings [#57330]
-   Patch - Add email editor files to the Woo Monorepo [#55598]
-   Patch - Add landingPage and block_email_editor feature flag [#55955]
-   Patch - Align email editor js package with Woo Monorepo configs [#56161]
-   Patch - Bump wireit dependency version to latest. [#57299]
-   Patch - Monorepo: build RAM usage optimization. [#58861]
-   Patch - Monorepo: complete migration from `classnames` package to `clsx`. [#58699]
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`. [#58941]
-   Patch - Monorepo: used babel transforms cleanup. [#56486]
-   Patch - Monorepo: watch startup time optimization. [#59166]
-   Patch - Monorepo: Webpack deps review and consolidation and a bit of deps grooming [#56746]
-   Patch - Update package json. Move copy assets commands to the @woocommerce/plugin-woocommerce package. The command will run when required. [#55754]
-   Patch - Add new filters for updating Email settings sidebar info content [#56365]
-   Patch - Move const with allowed blocks back to the PHP package. [#59070]
-   Minor - Add filter to remove the alignment controls for core/quote block [#57280]
-   Minor - Add loading allowed blocks in the email editor by metadata `supports.email`. [#58966]
-   Minor - Allow using Personalization Tags as button text or URL [#57958]
-   Minor - Enable Social Link block in the Email Editor. [#58194]
-   Minor - The trash model now supports permanent deletion option. Returning `true` for filter `woocommerce_email_editor_trash_modal_should_permanently_delete` will activate the permanent delete option (Skipping trash which is the default) [#57827]
-   Minor - Remove hardcoded post type and ID from constants in email editor store. This change is made to dynamically retrieve these values, enhancing flexibility and maintainability. [#59186]
-   Minor - This adds a back button to the WooCommerce Email Editor, allowing navigation back to the email listings page from the editor. The back button appears when the editor is in full-screen mode, which is now enforced by default. [#58154]
-   Minor - Update editor to support editing only email template without a content post [#57246]
-   Minor - Update email editor email preview, switch to template modal and style preview componets [#58078]
-   Minor - Update telemetry tracking to restore events removed by refactoring components [#58294]
-   Minor - Added email status toggle in the email editor sidebar. Added the ability to enable/disable transactional emails directly from the email editor [#57953]

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/email-editor/CHANGELOG.md).
