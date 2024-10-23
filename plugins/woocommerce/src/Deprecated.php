<?php

/**
 * Class Aliases for graceful Backwards compatibility.
 *
 * This file is autoloaded via composer.json and maps the old namespaces to deprecation handlers.
 */

use Automattic\WooCommerce\Admin\Features\Navigation\RemovedDeprecated;

class_alias( RemovedDeprecated::class, \Automattic\WooCommerce\Admin\Features\Navigation\Screen::class );
class_alias( RemovedDeprecated::class, \Automattic\WooCommerce\Admin\Features\Navigation\Menu::class );
class_alias( RemovedDeprecated::class, \Automattic\WooCommerce\Admin\Features\Navigation\CoreMenu::class );
