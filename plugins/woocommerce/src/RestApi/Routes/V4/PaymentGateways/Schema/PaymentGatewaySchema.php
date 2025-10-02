<?php
/**
 * PaymentGatewaySchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\PaymentGateways\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;
use WC_Payment_Gateway;
use WP_REST_Request;

/**
 * PaymentGatewaySchema class.
 *
 * Defines the schema for payment gateway objects in the REST API.
 *
 * The `settings` property is an object where keys are arbitrary setting IDs
 * and values are setting configuration objects with the following structure:
 *
 * - id (string, readonly): A unique identifier for the setting
 * - label (string, readonly): A human readable label for the setting used in interfaces
 * - description (string, readonly): A human readable description for the setting used in interfaces
 * - type (string, readonly): Type of setting (text, email, number, color, password, textarea, select, multiselect, radio, image_width, checkbox)
 * - value (string): Setting value
 * - default (string, readonly): Default value for the setting
 * - tip (string, readonly): Additional help text shown to the user about the setting
 * - placeholder (string, readonly): Placeholder text to be displayed in text inputs
 * - options (object, optional): Available options for select/multiselect type settings
 */
class PaymentGatewaySchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'payment-gateway';

	/**
	 * Return all properties for the item schema.
	 *
	 * Note that context determines under which context data should be visible. For example, edit would be the context
	 * used when getting records with the intent of editing them. embed context allows the data to be visible when the
	 * item is being embedded in another response.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'                 => array(
				'description' => __( 'Payment gateway ID.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'title'              => array(
				'description' => __( 'Payment gateway title on checkout.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'description'        => array(
				'description' => __( 'Payment gateway description on checkout.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'order'              => array(
				'description' => __( 'Payment gateway sort order.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'arg_options' => array(
					'sanitize_callback' => 'absint',
				),
			),
			'enabled'            => array(
				'description' => __( 'Payment gateway enabled status.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'method_title'       => array(
				'description' => __( 'Payment gateway method title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'method_description' => array(
				'description' => __( 'Payment gateway method description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'method_supports'    => array(
				'description' => __( 'Supported features for this payment gateway.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
				'items'       => array(
					'type' => 'string',
				),
			),
			'settings'           => array(
				'description' => __( 'Payment gateway settings. An object where keys are setting IDs and values are setting configuration objects.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
		);
	}

	/**
	 * Return settings associated with this payment gateway.
	 *
	 * Note: Some gateways may conditionally populate the 'options' array for select/multiselect fields
	 * based on context (e.g., only when accessing settings pages) for performance reasons.
	 * For example, the COD gateway's `enable_for_methods` field loads shipping method options only
	 * when `is_accessing_settings()` returns true. This means the options array may be empty when
	 * accessed via the REST API, even though the field type is multiselect.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 *
	 * @return array
	 */
	public function get_settings( WC_Payment_Gateway $gateway ): array {
		$settings = array();
		$gateway->init_form_fields();
		foreach ( $gateway->form_fields as $id => $field ) {
			// Make sure we at least have a title and type.
			if ( empty( $field['title'] ) || empty( $field['type'] ) ) {
				continue;
			}

			// Ignore 'enabled' and 'description' which get included elsewhere.
			if ( in_array( $id, array( 'enabled', 'description' ), true ) ) {
				continue;
			}

			$data = array(
				'id'          => $id,
				'label'       => empty( $field['label'] ) ? $field['title'] : $field['label'],
				'description' => empty( $field['description'] ) ? '' : $field['description'],
				'type'        => $field['type'],
				'value'       => empty( $gateway->settings[ $id ] ) ? '' : $gateway->settings[ $id ],
				'default'     => empty( $field['default'] ) ? '' : $field['default'],
				'tip'         => empty( $field['description'] ) ? '' : $field['description'],
				'placeholder' => empty( $field['placeholder'] ) ? '' : $field['placeholder'],
			);
			if ( ! empty( $field['options'] ) ) {
				$data['options'] = $field['options'];
			}
			$settings[ $id ] = $data;
		}
		return $settings;
	}

	/**
	 * Get the item response.
	 *
	 * @param WC_Payment_Gateway $gateway Payment gateway object.
	 * @param WP_REST_Request    $request Request object.
	 * @param array              $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $gateway, WP_REST_Request $request, array $include_fields = array() ): array {
		$order = (array) get_option( 'woocommerce_gateway_order' );
		return array(
			'id'                 => $gateway->id,
			'title'              => $gateway->title,
			'description'        => $gateway->description,
			'order'              => isset( $order[ $gateway->id ] ) ? $order[ $gateway->id ] : '',
			'enabled'            => ( 'yes' === $gateway->enabled ),
			'method_title'       => $gateway->get_method_title(),
			'method_description' => $gateway->get_method_description(),
			'method_supports'    => $gateway->supports,
			'settings'           => $this->get_settings( $gateway ),
		);
	}
}
