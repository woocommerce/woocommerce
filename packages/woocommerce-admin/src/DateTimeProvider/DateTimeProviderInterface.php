<?php
/**
 * Interface for a provider for getting the current DateTime,
 * designed to be mockable for unit tests.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\DateTimeProvider;

defined( 'ABSPATH' ) || exit;

/**
 * DateTime Provider Interface.
 */
interface DateTimeProviderInterface {
	/**
	 * Returns the current DateTime.
	 *
	 * @return DateTime
	 */
	public function get_now();
}
