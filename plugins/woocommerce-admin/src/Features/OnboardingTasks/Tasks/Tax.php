<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore as TaxDataStore;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Admin\PluginsHelper;

/**
 * Tax Task
 */
class Tax extends Task {

	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );
		add_action( 'admin_enqueue_scripts', array( $this, 'possibly_add_return_notice_script' ) );
	}

	/**
	 * Adds a return to task list notice when completing the task.
	 */
	public function possibly_add_return_notice_script() {
		$page = isset( $_GET['page'] ) ? $_GET['page'] : ''; // phpcs:ignore csrf ok, sanitization ok.
		$tab  = isset( $_GET['tab'] ) ? $_GET['tab'] : ''; // phpcs:ignore csrf ok, sanitization ok.

		if ( 'wc-settings' !== $page || 'tax' !== $tab ) {
			return;
		}

		if ( ! $this->is_active() || $this->is_complete() ) {
			return;
		}

		$script_assets_filename = Loader::get_script_asset_filename( 'wp-admin-scripts', 'onboarding-tax-notice' );
		$script_assets          = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $script_assets_filename;

		wp_enqueue_script(
			'onboarding-tax-notice',
			Loader::get_url( 'wp-admin-scripts/onboarding-tax-notice', 'js' ),
			array_merge( array( WC_ADMIN_APP ), $script_assets ['dependencies'] ),
			WC_VERSION,
			true
		);
	}

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'tax';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Set up tax', 'woocommerce-admin' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return self::can_use_automated_taxes()
			? __(
				'Good news! WooCommerce Services and Jetpack can automate your sales tax calculations for you.',
				'woocommerce-admin'
			)
			: __(
				'Set your store location and configure tax rate settings.',
				'woocommerce-admin'
			);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '1 minute', 'woocommerce-admin' );
	}

	/**
	 * Action label.
	 *
	 * @return string
	 */
	public function get_action_label() {
		return self::can_use_automated_taxes()
			? __( 'Yes please', 'woocommerce-admin' )
			: __( "Let's go", 'woocommerce-admin' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return get_option( 'wc_connect_taxes_enabled' ) ||
			count( TaxDataStore::get_taxes( array() ) ) > 0 ||
			false !== get_option( 'woocommerce_no_sales_tax' );
	}

	/**
	 * Addtional data.
	 *
	 * @return array
	 */
	public function get_additional_data() {
		return array(
			'avalara_activated'         => PluginsHelper::is_plugin_active( 'woocommerce-avatax' ),
			'tax_jar_activated'         => class_exists( 'WC_Taxjar' ),
			'woocommerce_tax_countries' => self::get_automated_support_countries(),
		);
	}

	/**
	 * Check if the store has any enabled gateways.
	 *
	 * @return bool
	 */
	public static function can_use_automated_taxes() {
		if ( ! class_exists( 'WC_Taxjar' ) ) {
			return false;
		}

		return in_array( WC()->countries->get_base_country(), self::get_automated_support_countries(), true );
	}

	/**
	 * Get an array of countries that support automated tax.
	 *
	 * @return array
	 */
	public static function get_automated_support_countries() {
		// https://developers.taxjar.com/api/reference/#countries .
		$tax_supported_countries = array_merge(
			array( 'US', 'CA', 'AU', 'GB' ),
			WC()->countries->get_european_union_countries()
		);

		return $tax_supported_countries;
	}
}
