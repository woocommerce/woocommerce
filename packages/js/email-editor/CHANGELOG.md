# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0](https://www.npmjs.com/package/@woocommerce/email-editor/v/1.0.0) - 2025-07-09 

-   Patch - Add fallback for Navigator component to the email editor [#58083]
-   Patch - Fix an error with the Email Editor not loading Global Styles for non-admin WordPress users and ensure permissions are correctly checked when required. [#56261]
-   Patch - Fix crash in the email editor when a third-party sidebar component throws an error [#58459]
-   Patch - Fix WordPress 6.8 compatibility [#56820]
-   Patch - Resolve event handler stacking in `useContentValidation()`. [#58247]
-   Minor - Add filter to remove the alignment controls for core/quote block [#57280]
-   Minor - Add loading allowed blocks in the email editor by metadata `supports.email`. [#58966]
-   Minor - Allow using Personalization Tags as button text or URL [#57958]
-   Minor - Enable Social Link block in the Email Editor. [#58194]
-   Minor - The trash model now supports permanent deletion option. Returning `true` for filter `woocommerce_email_editor_trash_modal_should_permanently_delete` will activate the permanent delete option (Skipping trash which is the default) [#57827]
-   Patch - Add a warning about border units to the email editor. [#58169]
-   Major [ **BREAKING CHANGE** ] - Refactor email editor to work on top of wordpress/editor Editor component [#57775]
-   Major [ **BREAKING CHANGE** ] - Refactor the package and its build to be prepared for publishing [#58874]
-   Patch - Remove "Swap template" from email editor [#56829]
-   Minor - Remove hardcoded post type and ID from constants in email editor store. This change is made to dynamically retrieve these values, enhancing flexibility and maintainability. [#59186]
-   Patch - Rename the details panel in the sidebar to settings [#57330]
-   Minor - This adds a back button to the WooCommerce Email Editor, allowing navigation back to the email listings page from the editor. The back button appears when the editor is in full-screen mode, which is now enforced by default. [#58154]
-   Minor - Update editor to support editing only email template without a content post [#57246]
-   Minor - Update email editor email preview, switch to template modal and style preview componets [#58078]
-   Minor - Update telemetry tracking to restore events removed by refactoring components [#58294]
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
-   Minor - Added email status toggle in the email editor sidebar. Added the ability to enable/disable transactional emails directly from the email editor [#57953]

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/email-editor/CHANGELOG.md).
