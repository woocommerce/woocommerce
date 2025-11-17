# WooCommerce Email Editor

This folder contains the code for the WooCommerce Email Editor PHP Package.

This package covers functionality for bootstrapping the email editor JS application and code for rendering emails from Gutenberg blocks.

You can locate the JS package in [`packages/js/email-editor`](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/email-editor)

## Workflow Commands

Note: The package is developed in [the WooCommerce monorepo](https://github.com/woocommerce/woocommerce/tree/trunk/packages/php/email-editor).

We use `composer` run scripts to run the commands. You can run them using `composer run <command>`.
If you don't have `composer` installed globally, you need to install it globally. [Please check how to install it](https://getcomposer.org/doc/00-intro.md).

```bash
composer run env:start                             # start testing environment
composer run env:stop                              # stop testing environment
composer run test:unit                             # runs all the unit tests
composer run test:unit -- [path_to_tests]          # runs a single unit test or a directory of tests
composer run test:integration                      # runs all the integrations tests
composer run test:integration -- [path_to_tests]   # run a single integration test or a directory of tests
composer code-style                                # checks the code style
```

## PHPStan Commands

The following commands are available via pnpm for running PHP static analysis:

```bash
pnpm run phpstan [--skip-cleanup]                  # PHP static analysis with PHPStan with PHP 8.4. When skip-cleanup is used the command does not delete installed dependencies.
pnpm run phpstan:php8 [--skip-cleanup]             # Alias for the command `phpstan`
pnpm run phpstan:php7 [--skip-cleanup]             # PHP static analysis with PHPStan with PHP 7.4
```

Example:

```bash
# To run test cases defined in tests/integration/Engine/Theme_Controller_Test.php run
composer run test:integration -- tests/integration/Engine/Theme_Controller_Test.php
```

More testing guide at [writing-tests.md](writing-tests.md)

## Development

The **PHP** package is divided into `engine` and `integrations` subdirectories.
Engine consist of code for the editor core and integrations are for extending the functionality.
Anything **WooCommerce** specific should be in the `plugins/woocommerce/src/Internal/EmailEditor` folder.

More development details at [development.md](development.md)

### Renderer

#### Content Renderer

-   Responsible for rendering saved template + email content to HTML or email clients
-   Flow is Preprocessors > BlocksRenderer > Postprocessors

#### Root Renderer.php

-   Takes the rendered content html and places it into email HTML template template-canvas.php (We have too many items we call "template" I know 🙁)

### Integrations

[Please locate MailPoet PHP integrations.](https://github.com/mailpoet/mailpoet/tree/13bf305aeb29bbadd0695ee02a3735e62cc4f21f/mailpoet/lib/EmailEditor/Integrations/MailPoet)

[WooCommerce Integration](https://github.com/woocommerce/woocommerce/tree/6dfd5f16aecbeee2fae0ec30e0c7ce7036cfeaac/plugins/woocommerce/src/Internal/EmailEditor)

## Known rendering issues

-   In some (not all) Outlook versions the width of columns is not respected. The columns will be rendered with the full width.

## Actions and Filters

These actions and filters are currently **Work-in-progress**.
We may add, update and delete any of them.

**Please use with caution**.

### Actions

| Name                                            | Argument         | Description                                                                                                      |
|-------------------------------------------------|------------------|------------------------------------------------------------------------------------------------------------------|
| `woocommerce_email_editor_initialized`          | `null`           | Called when the Email Editor is initialized                                                                      |
| `woocommerce_email_blocks_renderer_initialized` | `BlocksRegistry` | Called when the block content renderer is initialized. You may use this to add a new BlockRenderer               |
| `woocommerce_email_editor_register_templates`   |                  | Called when the basic blank email template is registered. You can add more templates via register_block_template |

### Filters

| Name                                                               | Argument                                                         | Return                                                       | Description                                                                                                                                                            |
|--------------------------------------------------------------------|------------------------------------------------------------------|--------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `woocommerce_email_editor_post_types`                              | `Array` $post_types                                              | `Array` EmailPostType                                        | Applied to the list of post types used by the `getPostTypes` method                                                                                                    |
| `woocommerce_email_editor_theme_json`                              | `WP_Theme_JSON` $core_theme_data                                 | `WP_Theme_JSON` $theme_json                                  | Applied to the theme json data. This theme json data is created from the merging of the `WP_Theme_JSON_Resolver::get_core_data` and WooCommerce owns `theme.json` file |
| `woocommerce_email_renderer_styles`                                | `string` $template_styles, `WP_Post` $post                       | `string` $template_styles                                    | Applied to the email editor template styles.                                                                                                                           |
| `woocommerce_email_content_renderer_styles`                        | `string` $content_styles, `WP_Post` $post                        | `string` $content_styles                                     | Applied to the inline content styles prior to use by the CSS Inliner.                                                                                                  |
| `woocommerce_is_email_editor_page`                                 | `boolean` $is_editor_page                                        | `boolean`                                                    | Check current page is the email editor page                                                                                                                            |
| `woocommerce_email_editor_send_preview_email`                      | `Array` $post_data                                               | `boolean` Result of processing. Was email sent successfully? | Allows override of the send preview mail function. Folks may choose to use custom implementation                                                                       |
| `woocommerce_email_editor_post_sent_status_args`                   | `Array` `sent` post status args                                  | `Array` register_post_status args                            | Allows update of the argument for the sent post status                                                                                                                 |
| `woocommerce_email_blocks_renderer_parsed_blocks`                  | `Array` Parsed blocks data                                       | `Array` Parsed blocks data                                   | You can modify the parsed blocks before they are processed by email renderer.                                                                                          |
| `woocommerce_email_editor_rendering_email_context`                 | `Array` $email_context                                           | `Array` $email_context                                       | Applied during email rendering to provide context data (e.g., `recipient_email`, `user_id`, `order_id`) to block renderers.                                            |
| `woocommerce_email_editor_send_preview_email_rendered_data`        | `string` $data Rendered email, `WP_Post` $post                                     | `string` Rendered email                                      | Allows modifying the rendered email when displaying or sending it in preview                                                                                           |
| `woocommerce_email_editor_send_preview_email_personalizer_context` | `string` $content_styles, `WP_Post` $post` $personalizer_context | `Array` Personalizer context data                            | Allows modifying the personalizer context data for the send preview email function                                                                                     |
| `woocommerce_email_editor_synced_site_styles`                      | `Array` $synced_data, `Array` $site_data                         | `Array` Modified synced data                                 | Used to filter the synced site style data before applying to email theme.                                                                                              |
| `woocommerce_email_editor_site_style_sync_enabled`                 | `bool` $enabled                                                  | `bool`                                                       | Use to control whether site style sync functionality is enabled or disabled. Returning `false` will disable site theme sync.                                           |
| `woocommerce_email_editor_allowed_iframe_style_handles`            | `Array` $allowed_iframe_style_handles                            | `Array` $allowed_iframe_style_handles                        | Filter the list of allowed stylesheet handles in the editor iframe.                                                                                                    |
| `woocommerce_email_editor_script_localization_data`                | `Array` $localization_data                                       | `Array` $localization_data                                   | Use to modify inlined JavaScript variables used by Email Editor client.                                                                                                |

## Logging

The email editor package includes logging functionality to help developers identify and debug issues. The package provides a simple logger that can either write to WordPress debug log or delegate to another logger.

### Usage

The logger is automatically initialized when the email editor is initialized. It's used internally by the email editor to log various events and operations.

### Log Levels

The following log levels are supported:

-   `emergency`: System is unusable
-   `alert`: Action must be taken immediately
-   `critical`: Critical conditions
-   `error`: Error conditions
-   `warning`: Warning conditions
-   `notice`: Normal but significant condition
-   `info`: Informational messages
-   `debug`: Debug-level messages

### Log Locations

By default, logs are written to the WordPress debug log if `WP_DEBUG_LOG` is enabled. The behavior depends on how `WP_DEBUG_LOG` is configured:

- If `WP_DEBUG_LOG` is set to `true`, logs are written to `wp-content/debug.log`
- If `WP_DEBUG_LOG` is set to a string path (e.g., `/path/to/custom/debug.log`), logs are written to that custom location
- If `WP_DEBUG_LOG` is not defined or set to `false`, logging is disabled

### Example Log Messages

The package logs various events, including:

1. Email editor initialization
2. Personalization tag registration
3. Duplicate personalization tag registration attempts

Example log entry:

```text
[2025-01-01 12:00:00] INFO: Initializing email editor
[2025-01-01 12:00:00] INFO: Personalization tags registry initialized {"tags_count": 15}
[2025-01-01 12:00:00] WARNING: Personalization tag already registered {"token": "[user/firstname]", "name": "First Name", "category": "User"}
```

### Customizing Logging

You can customize the logging behavior by:

1. Setting a delegate logger using `set_logger()` method to use another logging system (e.g., WooCommerce's logger)
2. Configuring WordPress debug logging through `WP_DEBUG_LOG` constant in wp-config.php to enable/disable logging to wp-content/debug.log

### Best Practices

1. Use appropriate log levels for different types of messages
2. Include relevant context data in log messages
3. Avoid logging sensitive information
4. Use debug level for detailed troubleshooting information
5. Use warning level for potential issues that don't prevent functionality
6. Use error level for issues that affect functionality
