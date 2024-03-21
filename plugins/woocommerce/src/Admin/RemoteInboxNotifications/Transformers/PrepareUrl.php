<?php

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers;

use Automattic\WooCommerce\Admin\DeprecatedClassFacade;

/**
 * Prepare site URL for comparison.
 *
 * @package Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers
 *
 * @deprecated 8.8.0
 */
class PrepareUrl extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\ArrayColumn';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '8.8.0';
}
