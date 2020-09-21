<?php
/**
 * Configuration file for PHP Scoper
 *
 * @package WooCommerce
 *
 * Original repository for the tool: https://github.com/humbug/php-scoper
 * The fork we are using for now: https://github.com/woocommerce/php-scoper
 *   We use the "with-improvements" branch, it contains the changes from the yet-to-be-merged pull requests
 *   made to the original repository.
 */

declare(strict_types=1);

return array(
	'on-existing-output-dir'       => 'overwrite',
	'output-dir'                   => '../vendor-scoped',
	'prefix'                       => 'Automattic\WooCommerce\Vendor',
	'inverse-namespaces-whitelist' => true,
	'whitelist-global-constants'   => true,
	'whitelist-global-classes'     => true,
	'whitelist-global-functions'   => true,

	// If a composer-installed tool needs to be prefixed to prevent conflicts with extensions using the same tool,
	// put its namespace here.
	// At "composer install" time all classes within those namespaces will get a "Automattic\WooCommerce\Vendor" prefix.
	'whitelist'                    => array(
		'League\Container\*',
	),
);
