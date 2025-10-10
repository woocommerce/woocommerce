<?php
/**
 * CodGatewaySettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

defined( 'ABSPATH' ) || exit;

use WC_Data_Store;
use WC_Shipping_Zone;

/**
 * CodGatewaySettingsSchema class.
 *
 * Extends AbstractPaymentGatewaySettingsSchema for Cash on Delivery payment gateway.
 *
 * Note: The COD gateway has enable_for_methods and enable_for_virtual fields
 * which are standard fields stored in gateway settings.
 */
class CodGatewaySettingsSchema extends AbstractPaymentGatewaySettingsSchema {

	/**
	 * Get options for specific COD gateway fields.
	 *
	 * @param string $field_id Field ID.
	 * @return array Field options.
	 */
	protected function get_field_options( string $field_id ): array {
		switch ( $field_id ) {
			case 'enable_for_methods':
				return $this->load_shipping_method_options();
			default:
				return array();
		}
	}

	/**
	 * Load all shipping method options for the enable_for_methods field.
	 *
	 * This method replicates the logic from WC_Gateway_COD::load_shipping_method_options()
	 * to provide shipping method options for the REST API without relying on the gateway class.
	 *
	 * @return array Nested array of shipping method options.
	 */
	private function load_shipping_method_options(): array {
		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();
		$zones      = array();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		$options = array();
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = array();

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'woocommerce' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'woocommerce' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'woocommerce' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'woocommerce' ), $option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}
}
