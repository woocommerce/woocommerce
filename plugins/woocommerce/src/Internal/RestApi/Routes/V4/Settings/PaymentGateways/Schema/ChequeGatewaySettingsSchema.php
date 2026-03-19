<?php
/**
 * ChequeGatewaySettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

defined( 'ABSPATH' ) || exit;

use WC_Payment_Gateway;

/**
 * ChequeGatewaySettingsSchema class.
 *
 * Extends AbstractPaymentGatewaySettingsSchema for the Check payment gateway
 * with design-aligned field labels and descriptions.
 */
class ChequeGatewaySettingsSchema extends AbstractPaymentGatewaySettingsSchema {

	/**
	 * Get custom groups for the cheque gateway.
	 *
	 * Provides design-aligned labels and descriptions for the check payment
	 * settings form fields. Derives fields from the gateway's form_fields
	 * to preserve any extension-injected settings.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 * @return array Custom group structure.
	 */
	protected function get_custom_groups_for_gateway( WC_Payment_Gateway $gateway ): array {
		// Design-aligned overrides for core fields.
		$core_field_overrides = array(
			'enabled'      => array(
				'label' => __( 'Enable/Disable', 'woocommerce' ),
				'type'  => 'checkbox',
				'desc'  => __( 'Enable check payments at checkout', 'woocommerce' ),
			),
			'title'        => array(
				'label' => __( 'Checkout label', 'woocommerce' ),
				'type'  => 'text',
				'desc'  => __( 'Shown to customers on the payment methods list at checkout.', 'woocommerce' ),
			),
			'description'  => array(
				'label' => __( 'Checkout instructions', 'woocommerce' ),
				'type'  => 'text',
				'desc'  => __( 'Shown below the checkout label.', 'woocommerce' ),
			),
			'order'        => array(
				'label' => __( 'Order', 'woocommerce' ),
				'type'  => 'number',
				'desc'  => __( 'Determines the display order of payment gateways during checkout.', 'woocommerce' ),
			),
			// Intentionally differs from BACS/COD ("Order confirmation instructions") per design spec.
			'instructions' => array(
				'label' => __( 'Instructions shown after checkout', 'woocommerce' ),
				'type'  => 'text',
				'desc'  => __( 'Shown on the order confirmation page and in order emails.', 'woocommerce' ),
			),
		);

		$fields = $this->build_fields_from_form_fields( $gateway, $core_field_overrides );

		$group = array(
			'title'       => __( 'Check payment settings', 'woocommerce' ),
			'description' => __( 'Manage how check payments appear at checkout and in order emails.', 'woocommerce' ),
			'order'       => 1,
			'fields'      => $fields,
		);

		return array( 'settings' => $group );
	}
}
