<?php
/**
 * Evaluate the given rules as an AND operation - return false early if a
 * rule evaluates to false.
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\DeprecatedClassFacade;

/**
 * Evaluate the given rules as an AND operation - return false early if a
 * rule evaluates to false.
 *
 * @deprecated 8.8.0
 */
class RuleEvaluator extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '8.8.0';
}
