<?php
/**
 * Rule processor for sending when WooCommerce Admin has been updated.
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\DeprecatedClassFacade;

/**
 * Rule processor for sending when WooCommerce Admin has been updated.
 *
 * @deprecated 8.8.0
 */
class WooCommerceAdminUpdatedRuleProcessor extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\WooCommerceAdminUpdatedRuleProcessor';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '8.8.0';
}
