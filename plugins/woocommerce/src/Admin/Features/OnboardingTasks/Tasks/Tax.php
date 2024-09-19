<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore as TaxDataStore;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Tax Task
 */
class Tax extends Task {

	/**
	 * Used to cache is_complete() method result.
	 *
	 * @var null
	 */
	private $is_complete_result = null;

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

		if ( $page !== 'wc-settings' || $tab !== 'tax' ) {
			return;
		}

		if ( ! $this->is_active() || $this->is_complete() ) {
			return;
		}

		WCAdminAssets::register_script( 'wp-admin-scripts', 'onboarding-tax-notice', true );
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
		return __( 'Collect sales tax', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return self::can_use_automated_taxes()
			? __(
				'Good news! WooCommerce Tax can automate your sales tax calculations for you.',
				'woocommerce'
			)
			: __(
				'Set your store location and configure tax rate settings.',
				'woocommerce'
			);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '1 minute', 'woocommerce' );
	}

	/**
	 * Action label.
	 *
	 * @return string
	 */
	public function get_action_label() {
		return self::can_use_automated_taxes()
			? __( 'Yes please', 'woocommerce' )
			: __( "Let's go", 'woocommerce' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		if ( $this->is_complete_result === null ) {
			$wc_connect_taxes_enabled    = get_option( 'wc_connect_taxes_enabled' );
			$is_wc_connect_taxes_enabled = ( $wc_connect_taxes_enabled === 'yes' ) || ( $wc_connect_taxes_enabled === true ); // seems that in some places boolean is used, and other places 'yes' | 'no' is used

			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- We will replace this with a formal system by WC 9.6 so lets not advertise it yet.
			$third_party_complete = apply_filters( 'woocommerce_admin_third_party_tax_setup_complete', false );

			$this->is_complete_result = $is_wc_connect_taxes_enabled ||
				count( TaxDataStore::get_taxes( array() ) ) > 0 ||
				get_option( 'woocommerce_no_sales_tax' ) !== false ||
				$third_party_complete;
		}

		return $this->is_complete_result;
	}

	/**
	 * Additional data.
	 *
	 * @return array
	 */
	public function get_additional_data() {
		return array(
			'avalara_activated'              => PluginsHelper::is_plugin_active( 'woocommerce-avatax' ),
			'tax_jar_activated'              => class_exists( 'WC_Taxjar' ),
			'stripe_tax_activated'           => PluginsHelper::is_plugin_active( 'stripe-tax-for-woocommerce' ),
			'woocommerce_tax_activated'      => PluginsHelper::is_plugin_active( 'woocommerce-tax' ),
			'woocommerce_shipping_activated' => PluginsHelper::is_plugin_active( 'woocommerce-shipping' ),
			'woocommerce_tax_countries'      => self::get_automated_support_countries(),
			'stripe_tax_countries'           => self::get_stripe_tax_support_countries(),
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

	/**
	 * Get an array of countries that support Stripe tax.
	 *
	 * @return array
	 */
	private static function get_stripe_tax_support_countries() {
		// https://docs.stripe.com/tax/supported-countries#supported-countries accurate as of 2024-08-26.
		// countries with remote sales not included.
		return array(
			'AU',
			'AT',
			'BE',
			'BG',
			'CA',
			'HR',
			'CY',
			'CZ',
			'DK',
			'EE',
			'FI',
			'FR',
			'DE',
			'GR',
			'HK',
			'HU',
			'IE',
			'IT',
			'JP',
			'LV',
			'LT',
			'LU',
			'MT',
			'NL',
			'NZ',
			'NO',
			'PL',
			'PT',
			'RO',
			'SG',
			'SK',
			'SI',
			'ES',
			'SE',
			'CH',
			'AE',
			'GB',
			'US',
		);
	}
}
