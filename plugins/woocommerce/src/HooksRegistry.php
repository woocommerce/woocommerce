<?php
declare(strict_types=1);

namespace Automattic\WooCommerce;

/**
 * This class list and register core hooks for WooCommerce, without loading the file that contains the hooks. Classes that provides these hooks should be loaded by an Autoloader.
 */
class HooksRegistry {

	static array $all_request_actions = array(

	);

	static array $all_request_filters = array(

	);

	static array $frontend_actions = array(

	);

	static array $frontend_filters = array(

	);

	static array $admin_actions = array(

	);

	static array $admin_filters = array(

	);

}
