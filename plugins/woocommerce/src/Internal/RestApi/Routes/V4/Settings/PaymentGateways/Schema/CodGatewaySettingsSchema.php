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
use WC_Payment_Gateway;
use WC_Shipping_Zone;

/**
 * CodGatewaySettingsSchema class.
 *
 * Extends AbstractPaymentGatewaySettingsSchema for Cash on Delivery payment gateway
 * with design-aligned field labels and descriptions.
 */
class CodGatewaySettingsSchema extends AbstractPaymentGatewaySettingsSchema {

	/**
	 * Get custom groups for the COD gateway.
	 *
	 * Provides design-aligned labels and descriptions for the cash on delivery
	 * settings form fields.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array Custom group structure.
	 */
	protected function get_custom_groups_for_gateway( WC_Payment_Gateway $gateway ): array {
		$group = array(
			'title'       => __( 'Cash on delivery settings', 'woocommerce' ),
			'description' => __( 'Manage how Cash on delivery appears at checkout and in order emails.', 'woocommerce' ),
			'order'       => 1,
			'fields'      => array(
				array(
					'id'    => 'enabled',
					'label' => __( 'Enable/Disable', 'woocommerce' ),
					'type'  => 'checkbox',
					'desc'  => __( 'Enable Cash on delivery at checkout', 'woocommerce' ),
				),
				array(
					'id'    => 'title',
					'label' => __( 'Checkout label', 'woocommerce' ),
					'type'  => 'text',
					'desc'  => __( 'Shown to customers on the payment methods list at checkout.', 'woocommerce' ),
				),
				array(
					'id'    => 'description',
					'label' => __( 'Checkout instructions', 'woocommerce' ),
					'type'  => 'text',
					'desc'  => __( 'Shown below the checkout label.', 'woocommerce' ),
				),
				array(
					'id'    => 'order',
					'label' => __( 'Order', 'woocommerce' ),
					'type'  => 'number',
					'desc'  => __( 'Determines the display order of payment gateways during checkout.', 'woocommerce' ),
				),
				array(
					'id'    => 'instructions',
					'label' => __( 'Order confirmation instructions', 'woocommerce' ),
					'type'  => 'text',
					'desc'  => __( 'Shown on the order confirmation page and in order emails.', 'woocommerce' ),
				),
				array(
					'id'      => 'enable_for_methods',
					'label'   => __( 'Available for shipping methods', 'woocommerce' ),
					'type'    => 'multiselect',
					'desc'    => '',
					'options' => $this->load_shipping_method_options(),
				),
				array(
					'id'    => 'enable_for_virtual',
					'label' => __( 'Accept for virtual orders', 'woocommerce' ),
					'type'  => 'checkbox',
					'desc'  => __( 'Accept COD if the order is virtual', 'woocommerce' ),
				),
			),
		);

		return array( 'settings' => $group );
	}

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
