# MailPoet Email Renderer

**The renderer is Work In Progress (WIP) and so is the API for adding support email rendering for new blocks**.

## Adding support for a core block

1. Add block into `ALLOWED_BLOCK_TYPES` in `packages/php/email-editor/src/Engine/SettingsController.php`.
2. Make sure the block is registered in the editor. Currently all core blocks are registered in the editor.
3. If necessary, add BlockRender class (e.g. Heading) into `packages/php/email-editor/src/Integrations/Core/Renderer/Blocks/` folder.

```php
<?php declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;

class Heading extends AbstractBlockRenderer {
  protected function renderContent($blockContent, array $parsedBlock, Settings_Controller $settingsController): string {
    return $blockContent;
  }
}

```

<!-- markdownlint-disable MD029 -->

[//]: # 'This disabled MD029/ol-prefix Ordered list item prefix [Expected: 1; Actual: 4; Style: 1/1/1]'

4. Register the renderer

```php
<?php

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Blocks_Registry;

add_action('woocommerce_blocks_renderer_initialized', 'register_my_block_email_renderer');

function register_my_block_email_renderer(Blocks_Registry $blocksRegistry): void {
  $blocksRegistry->add_block_renderer('core/heading', new Renderer\Blocks\Heading());
}
```

Note: For core blocks this is currently done in `packages/php/email-editor/src/Integrations/Core/Initializer.php`.

5. Implement the rendering logic in the renderer class.

## Tips for adding support for block

-   You can take inspiration on block rendering from MJML in the <https://mjml.io/try-it-live>
-   Test the block in different clients [Litmus](https://litmus.com/)
-   You can take some inspirations from the HTML renderer by the old email editor

## TODO

-   add support for all core blocks
-   move the renderer to separate package
