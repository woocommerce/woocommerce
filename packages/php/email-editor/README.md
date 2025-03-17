# WooCommerce Email Editor

This folder contains the code for the WooCommerce Email Editor PHP Package.
We aim to extract the engine as an independent library, so it can be used in other projects.
As we are still in an exploration phase, we keep it together with the WooCommerce codebase.

You can locate the JS package here `packages/js/email-editor`

## Workflow Commands

We use `composer` run scripts to run the commands. You can run them using `composer run <command>`.
If you don't have `composer` installed globally, you need to install it globally. Please check ho to do it [here](https://getcomposer.org/doc/00-intro.md).

```bash
composer run env:start                             # start testing environment
composer run env:stop                              # stop testing environment
composer run test:unit                             # runs all the unit tests
composer run test:unit -- [path_to_tests]          # runs a single unit test or a directory of tests
composer run test:integration                      # runs all the integrations tests
composer run test:integration -- [path_to_tests]   # run a single integration test or a directory of tests
composer code-style                                # checks the code style
```

Example:

```bash
# To run test cases defined in tests/integration/Engine/Theme_Controller_Test.php run
composer run integration-test -- tests/integration/Engine/Theme_Controller_Test.php
```

## Development

The **PHP** package is divided into `engine` and `integrations` subdirectories.
Engine consist of code for the editor core and integrations are for extending the functionality.
Anything **WooCommerce** specific should be in the `plugins/woocommerce/src/Internal/EmailEditor` folder.

Please avoid using MailPoet-specific services and modules in the Email editor package.

### Renderer

#### Content Renderer

-   Responsible for rendering saved template + email content to HTML or email clients
-   Flow is Preprocessors > BlocksRenderer > Postprocessors

#### Root Renderer.php

-   Takes the rendered content html and places it into email HTML template template-canvas.php (We have too many items we call "template" I know 🙁)

### Integrations

Please locate MailPoet PHP integrations [here](https://github.com/mailpoet/mailpoet/tree/13bf305aeb29bbadd0695ee02a3735e62cc4f21f/mailpoet/lib/EmailEditor/Integrations/MailPoet)

## Known rendering issues

-   In some (not all) Outlook versions the width of columns is not respected. The columns will be rendered with the full width.

## Actions and Filters

These actions and filters are currently **Work-in-progress**.
We may add, update and delete any of them.

**Please use with caution**.

### Actions

| Name                                          | Argument         | Description                                                                                                      |
| --------------------------------------------- | ---------------- | ---------------------------------------------------------------------------------------------------------------- |
| `woocommerce_email_editor_initialized`        | `null`           | Called when the Email Editor is initialized                                                                      |
| `woocommerce_blocks_renderer_initialized`     | `BlocksRegistry` | Called when the block content renderer is initialized. You may use this to add a new BlockRenderer               |
| `woocommerce_email_editor_register_templates` |                  | Called when the basic blank email template is registered. You can add more templates via register_block_template |

### Filters

| Name                                             | Argument                                  | Return                                                       | Description                                                                                                                                                            |
| ------------------------------------------------ | ----------------------------------------- | ------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `woocommerce_email_editor_post_types`            | `Array` $postTypes                        | `Array` EmailPostType                                        | Applied to the list of post types used by the `getPostTypes` method                                                                                                    |
| `woocommerce_email_editor_theme_json`            | `WP_Theme_JSON` $coreThemeData            | `WP_Theme_JSON` $themeJson                                   | Applied to the theme json data. This theme json data is created from the merging of the `WP_Theme_JSON_Resolver::get_core_data` and WooCommerce owns `theme.json` file |
| `woocommerce_email_renderer_styles`              | `string` $templateStyles, `WP_Post` $post | `string` $templateStyles                                     | Applied to the email editor template styles.                                                                                                                           |
| `woocommerce_email_content_renderer_styles`      | `string` $contentStyles, `WP_Post` $post  | `string` $contentStyles                                      | Applied to the inline content styles prior to use by the CSS Inliner.                                                                                                  |
| `woocommerce_is_email_editor_page`               | `boolean` $isEditorPage                   | `boolean`                                                    | Check current page is the email editor page                                                                                                                            |
| `woocommerce_email_editor_send_preview_email`    | `Array` $postData                         | `boolean` Result of processing. Was email sent successfully? | Allows override of the send preview mail function. Folks may choose to use custom implementation                                                                       |
| `woocommerce_email_editor_post_sent_status_args` | `Array` `sent` post status args           | `Array` register_post_status args                            | Allows update of the argument for the sent post status                                                                                                                 |
