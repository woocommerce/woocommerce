<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\PluginsHelper;
use WC_Data_Store;

/**
 * Shipping Task
 */
class Shipping extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'shipping';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		if ( count( $this->task_list->get_sections() ) > 0 && ! $this->is_complete() ) {
			return __( 'Select how to ship your products', 'woocommerce' );
		}
		if ( true === $this->get_parent_option( 'use_completed_title' ) ) {
			if ( $this->is_complete() ) {
				return __( 'You added shipping costs', 'woocommerce' );
			}
			return __( 'Add shipping costs', 'woocommerce' );
		}
		return __( 'Set up shipping', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		if ( count( $this->task_list->get_sections() ) > 0 ) {
			return __( 'Set delivery costs and enable extra features, like shipping label printing.', 'woocommerce' );
		}
		return __(
			"Set your store location and where you'll ship to.",
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
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return self::has_shipping_zones();
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		if ( Features::is_enabled( 'shipping-smart-defaults' ) ) {
			if ( self::sell_only_digital_type() ) {
				return false;
			}

			$default_store_country = wc_format_country_state_string( get_option( 'woocommerce_default_country', '' ) )['country'];

			// Check if a store address is set so that we don't default to WooCommerce's default country US.
			$store_country = '';
			if ( ! empty( get_option( 'woocommerce_store_address', '' ) ) || 'US' !== $default_store_country ) {
				$store_country = $default_store_country;
			}

			if ( empty( $store_country ) ) {
				return true;
			}

			return in_array( $store_country, array( 'AU', 'CA', 'UK' ), true );
		}

		return self::has_physical_products();
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return self::has_shipping_zones()
			? admin_url( 'admin.php?page=wc-settings&tab=shipping' )
			: null;
	}

	/**
	 * Check if the store has any shipping zones.
	 *
	 * @return bool
	 */
	public static function has_shipping_zones() {
		return count( WC_Data_Store::load( 'shipping-zone' )->get_zones() ) > 0;
	}

	/**
	 * Check if the store has physical products.
	 *
	 * @return bool
	 */
	public static function has_physical_products() {
		$profiler_data = get_option( OnboardingProfile::DATA_OPTION, array() );
		$product_types = isset( $profiler_data['product_types'] ) ? $profiler_data['product_types'] : array();

		return in_array( 'physical', $product_types, true ) ||
			count(
				wc_get_products(
					array(
						'virtual' => false,
						'limit'   => 1,
					)
				)
			) > 0;
	}

	/**
	 * Check if the store sells digital products only.
	 *
	 * @return bool
	 */
	private static function sell_only_digital_type() {
		$profiler_data = get_option( OnboardingProfile::DATA_OPTION, array() );
		$product_types = isset( $profiler_data['product_types'] ) ? $profiler_data['product_types'] : array();

		return [ 'downloads' ] === $product_types;
	}
}
