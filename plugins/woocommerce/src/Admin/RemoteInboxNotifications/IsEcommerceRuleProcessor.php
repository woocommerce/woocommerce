<?php
/**
 * Rule processor that passes (or fails) when the site is on the eCommerce
 * plan.
 *
 * @package WooCommerce\Admin\Classes
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\DeprecatedClassFacade;

/**
 * Rule processor that passes (or fails) when the site is on the eCommerce
 * plan.
 *
 * @deprecated 8.8.0
 */
class IsEcommerceRuleProcessor extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\IsEcommerceRuleProcessor';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '8.8.0';
}
