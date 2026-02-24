<?php
/**
 * OfflinePaymentMethodSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\OfflinePaymentMethods\Schema;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
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
				'description' => __( 'Unique identifier for the settings group.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'title'       => array(
				'description' => __( 'Title of the settings group.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'description' => array(
				'description' => __( 'Description of the settings group.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'values'      => array(
				'description'          => __( 'Current enabled state for all payment methods.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => self::VIEW_EDIT_CONTEXT,
				'readonly'             => true,
				'additionalProperties' => array(
					'type' => 'boolean',
				),
			),
			'groups'      => array(
				'description' => __( 'Grouped settings for offline payment methods.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
				'properties'  => array(
					'payment_methods' => array(
						'description'          => __( 'Available offline payment methods.', 'woocommerce' ),
						'type'                 => 'object',
						'context'              => self::VIEW_EDIT_CONTEXT,
						'readonly'             => true,
						'additionalProperties' => array(
							'type'       => 'object',
							'properties' => array(
								'id'          => array(
									'description' => __( 'Unique identifier for the payment method.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => self::VIEW_EDIT_CONTEXT,
								),
								'_order'      => array(
									'description' => __( 'Sort order for the payment method.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => self::VIEW_EDIT_CONTEXT,
								),
								'title'       => array(
									'description' => __( 'Title of the payment method.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => self::VIEW_EDIT_CONTEXT,
								),
								'description' => array(
									'description' => __( 'Description of the payment method.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => self::VIEW_EDIT_CONTEXT,
								),
								'icon'        => array(
									'description' => __( 'Icon URL for the payment method.', 'woocommerce' ),
									'type'        => 'string',
									'format'      => 'uri',
									'context'     => self::VIEW_EDIT_CONTEXT,
								),
								'state'       => array(
									'description'          => __( 'Current state configuration of the payment method.', 'woocommerce' ),
									'type'                 => 'object',
									'context'              => self::VIEW_EDIT_CONTEXT,
									'additionalProperties' => array(
										'type' => 'boolean',
									),
								),
								'management'  => array(
									'description'          => __( 'Management options for the payment method.', 'woocommerce' ),
									'type'                 => 'object',
									'context'              => self::VIEW_EDIT_CONTEXT,
									'properties'           => array(
										'_links' => array(
											'description' => __( 'Management links for the payment method.', 'woocommerce' ),
											'type'        => 'object',
											'context'     => self::VIEW_EDIT_CONTEXT,
											'additionalProperties' => array(
												'type' => 'object',
												'properties' => array(
													'href' => array(
														'description' => __( 'URL for the management link.', 'woocommerce' ),
														'type'        => 'string',
														'format'      => 'uri',
														'context'     => self::VIEW_EDIT_CONTEXT,
													),
												),
												'additionalProperties' => false,
											),
										),
									),
									'additionalProperties' => false,
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
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) $request is unused; filtering handled by REST server.
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		$response = (array) $item;

		if ( ! empty( $include_fields ) ) {
			$response = array_intersect_key( $response, array_flip( $include_fields ) );
		}

		return $response;
	}
}
