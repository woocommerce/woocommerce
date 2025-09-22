# Changelog 

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.6.0](https://github.com/woocommerce/email-editor/releases/tag/1.6.0) - 2025-09-18 

-   Patch - Downgrade Emogrifier dependency to avoid conflict [#60994]
-   Minor - Add email rendering instructions for the core/audio and core/embed blocks. [#60813]
-   Minor - Add email rendering instructions for the core/cover block. [#60837]
-   Minor - Add email rendering instructions for the core/gallery block. [#60775]

## [1.5.0](https://github.com/woocommerce/email-editor/releases/tag/1.5.0) - 2025-09-09 

-   Patch - Add type check when extracting vars from theme style values in email editor [#60538]
-   Patch - Upgrade pelago/emogrifier to v8.0 [#60489]
-   Patch - Use a more robust way to post-process the style attribute values within the Email Editor. [#60764]
-   Minor - Add email block renderer for the Table core block. [#60514]
-   Minor - Add email rendering instructions for the core/media-text block. [#60752]
-   Minor - Remove `block_preview_url` from `WooCommerceEmailEditor` object [#60603]

## [1.4.2](https://github.com/woocommerce/email-editor/releases/tag/1.4.2) - 2025-08-21 

-   Patch - Filter unnecessary stylesheets from iframe assets [#60354]
-   Patch - Fix Email editor conflict with the site editor. [#60465]
-   Patch - Fix horizontal scrolling issue in the email editor on mobile devices. [#60355]
-   Patch - Use custom log filepath defined in WP_DEBUG_LOG when specified. [#60255]

## [1.4.1](https://github.com/woocommerce/email-editor/releases/tag/1.4.1) - 2025-08-08 

-   Patch - Introduce new class Assets_Manager to simplify integration. [#60165]

## [1.4.0](https://github.com/woocommerce/email-editor/releases/tag/1.4.0) - 2025-07-31 

-   Minor - Enable Site Logo and Site Title blocks for the Email Editor [#59624]
-   Patch - Add filtering personalization tags by the email post type. [#60072]
-   Patch - Add the new post_types property to the Abstract_Pattern class. [#60015]

## [1.3.0](https://github.com/woocommerce/email-editor/releases/tag/1.3.0) - 2025-07-24 

-   Patch - Integrate the convert class from the external HTML to Text library and remove the library dependency from the Email Editor package. [#59859]
-   Minor - Add functionality to sync block theme styles to the email editor. [#59757]
-   Minor - Add licensing and security policy. [#59859]

## [1.2.0](https://github.com/woocommerce/email-editor/releases/tag/1.2.0) - 2025-07-23 

-   Patch - Fix color inheritance in Paragraph and Heading blocks. [#59732]
-   Patch - Add documentation for block registration in the WooCommerce Email Editor [#59541]
-   Minor - Add `Styles_Helper` methods to generate inline styles from block attributes, and refactor blocks to utilize them. [#59678]

## [1.1.0](https://github.com/woocommerce/email-editor/releases/tag/1.1.0) - 2025-07-16 

-   Minor - Add Table_Wrapper_Helper utility class. [#59264]
-   Minor - Preserve personalization tags in email text version
-   Patch - Mark the emogrifier package a production dependency
-   Patch - Add documentation for Personalization Tags [#59226]

## [1.0.0](https://github.com/woocommerce/email-editor/releases/tag/1.0.0) - 2025-06-27 

-   Patch - Address PHP 8.4 deprecation warnings. [#57722]
-   Patch - Fix default rendering mode for WordPress 6.8 [#56820]
-   Patch - Fixed parsing empty argument values in personalization tags. [#58500]
-   Patch - Fix tiny rendering issues with image borders, list padding when the background color is set, and top margin for cite in the quote block. [#58796]
-   Patch - Sending correct email when user create an account after placing an order. [#57689]
-   Patch - Refactor Email Editor Container to use email editor container instead of Blocks registry container for better library export compatibility. [#59209]
-   Patch - Add command for PHP static analysis. [#58135]
-   Patch - Add email editor files to the Woo Monorepo [#55598]
-   Patch - Introduce a new Rendering_Context class that replaces Settings_Controller in renderer classes [#58796]
-   Patch - Monorepo: consolidate packages licenses to `GPL-2.0-or-later`. [#58941]
-   Patch - Remove unused Codeception config file for the email-editor package [#55971]
-   Patch - Remove usage of `settings.allowedBlockTypes` from the email editor configuration. [#58966]
-   Patch - Update package.json commands [#56161]
-   Patch - Add possibility to get current context to for personalization [#57330]
-   Patch - Fixed social links block styling by adding explicit margin-right:0 to prevent unwanted spacing on social icon images [#59188]
-   Patch - Add theme color pallete to base theme and remove the default heading color and use text color as fallback [#58078]
-   Patch - Ensure "Preview in new tab" shows the lastest editor saved content. [#58481]
-   Patch - Use email templates registry when listing allowed templates for association with an email post [#56110]
-   Minor - Add autosave timeout and disable code editor in editor settings [#57775]
-   Minor - Add email block renderer for the Quote core block. [#57280]
-   Minor - Add support for rendering Social Link and Social Links block in the Email Editor. [#58194]
-   Minor - Add Woo email content to the preview in the email editor [#57337]
-   Minor - Add `woocommerce_email_editor_send_preview_email_personalizer_context` filter to modify the personalizer context data for the send preview email function [#57795]
-   Minor - Handle Personalization Tags in href attributes [#57958]
-   Minor - Implement logging support in the email editor [#58607]
-   Minor - Add support for a block custom callback render_email_callback and remove Blocks_Registry class. [#59070]
-   Minor - Update package for publishing to Packagist [#59058]
