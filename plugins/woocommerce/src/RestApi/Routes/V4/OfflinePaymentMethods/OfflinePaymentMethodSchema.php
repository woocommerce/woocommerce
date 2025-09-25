<?php
/**
 * OfflinePaymentMethodSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\OfflinePaymentMethods;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * OfflinePaymentMethodSchema class.
 */
class OfflinePaymentMethodSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'offline_payment_method';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'          => array(
				'description' => __( 'The unique identifier for the payment method.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'_order'      => array(
				'description' => __( 'The sort order of the payment method.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'_type'       => array(
				'description' => __( 'The type of payment provider.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'title'       => array(
				'description' => __( 'The title of the payment method.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'description' => array(
				'description' => __( 'The description of the payment method.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'supports'    => array(
				'description' => __( 'Supported features for this payment method.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
				'items'       => array(
					'type' => 'string',
				),
			),
			'state'       => array(
				'description' => __( 'The general state of the payment method.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
				'properties'  => array(
					'enabled'           => array(
						'description' => __( 'Whether the payment method is enabled for use on checkout.', 'woocommerce' ),
						'type'        => 'boolean',
						'context'     => self::VIEW_EDIT_CONTEXT,
						'readonly'    => true,
					),
					'account_connected' => array(
						'description' => __( 'Whether the payment method has a processing account connected.', 'woocommerce' ),
						'type'        => 'boolean',
						'context'     => self::VIEW_EDIT_CONTEXT,
						'readonly'    => true,
					),
					'needs_setup'       => array(
						'description' => __( 'Whether the payment method needs setup.', 'woocommerce' ),
						'type'        => 'boolean',
						'context'     => self::VIEW_EDIT_CONTEXT,
						'readonly'    => true,
					),
					'test_mode'         => array(
						'description' => __( 'Whether the payment method is in test mode.', 'woocommerce' ),
						'type'        => 'boolean',
						'context'     => self::VIEW_EDIT_CONTEXT,
						'readonly'    => true,
					),
				),
			),
			'management'  => array(
				'description' => __( 'The management details of the payment method.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
				'properties'  => array(
					'_links' => array(
						'description' => __( 'Links related to payment method management.', 'woocommerce' ),
						'type'        => 'object',
						'context'     => self::VIEW_EDIT_CONTEXT,
						'readonly'    => true,
						'properties'  => array(
							'settings' => array(
								'description' => __( 'The link to the settings page for the payment method.', 'woocommerce' ),
								'type'        => 'object',
								'context'     => self::VIEW_EDIT_CONTEXT,
								'readonly'    => true,
								'properties'  => array(
									'href' => array(
										'description' => __( 'The URL to the settings page for the payment method.', 'woocommerce' ),
										'type'        => 'string',
										'context'     => self::VIEW_EDIT_CONTEXT,
										'readonly'    => true,
									),
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the item response.
	 *
	 * @param mixed           $item Payment method data array.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		// Since our $item is already in the correct format from PaymentsProviders,
		// we can return it directly. The schema will handle validation.
		return $item;
	}
}