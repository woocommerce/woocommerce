<?php
/**
 * Rule processor that passes when a store's payments volume exceeds a provided amount.
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\DeprecatedClassFacade;

/**
 * Rule processor that passes when a store's payments volume exceeds a provided amount.
 *
 * @deprecated 8.8.0
 */
class TotalPaymentsVolumeProcessor extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\TotalPaymentsVolumeProcessor';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '8.8.0';
}
