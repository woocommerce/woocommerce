<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\WCAdminHelper;

/**
 * Launch Your Store Task
 */
class LaunchYourStore extends Task {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_installed', array( $this, 'add_default_values' ) );
		add_action( 'woocommerce_updated', array( $this, 'add_default_values' ) );
	}

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'launch_your_store';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Launch your store', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			"It's time to celebrate – you're ready to launch your store! Woo! Hit the button to preview your store and make it public.",
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return '';
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		$launch_status = get_option( 'launch-status' );

		// The site is launched when the launch status is 'launched' or missing.
		$launched_values = array(
			'launched',
			'',
			false,
		);
		return in_array( $launch_status, $launched_values, true );
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return Features::is_enabled( 'launch-your-store' );
	}

	/**
	 * Set default option values for the task.
	 */
	public function add_default_values() {
		$coming_soon      = current_action() === 'woocommerce_installed' ? 'yes' : 'no';
		$store_pages_only = WCAdminHelper::is_site_fresh() ? 'yes' : 'no';
		$private_link     = 'yes';
		$share_key        = wp_generate_password( 32, false );

		if ( ! get_option( 'woocommerce_coming_soon' ) ) {
			update_option( 'woocommerce_coming_soon', $coming_soon );
		}
		if ( ! get_option( 'woocommerce_store_pages_only' ) ) {
			update_option( 'woocommerce_store_pages_only', $store_pages_only );
		}
		if ( ! get_option( 'woocommerce_private_link' ) ) {
			update_option( 'woocommerce_private_link', $private_link );
		}
		if ( ! get_option( 'woocommerce_share_key' ) ) {
			update_option( 'woocommerce_share_key', $share_key );
		}
	}
}
