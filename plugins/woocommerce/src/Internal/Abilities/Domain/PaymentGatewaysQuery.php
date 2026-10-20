<?php
/**
 * Payment gateways query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Lists payment gateways from their public properties only. Gateway settings
 * hold API keys and passwords, so they are never read.
 */
class PaymentGatewaysQuery extends AbstractDomainAbility implements AbilityDefinition {

	/**
	 * Ability ID, stable once released.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/payment-gateways-query';
	}

	/**
	 * Read-only, shop-manager-level registration.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query payment gateways', 'woocommerce' ),
			'description'         => __(
				'List the payment gateways in checkout order, enabled or not, or get one by ID.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_query_payment_gateways' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Gateways in the merchant's checkout order; one gateway when an ID is given.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public static function execute( array $input ) {
		$gateways = function_exists( 'WC' ) && WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();

		if ( isset( $input['id'] ) ) {
			$id = (string) $input['id'];

			if ( ! isset( $gateways[ $id ] ) ) {
				return new \WP_Error(
					'woocommerce_payment_gateway_not_found',
					__( 'Payment gateway not found.', 'woocommerce' ),
					array( 'status' => 404 )
				);
			}

			$gateways = array( $gateways[ $id ] );
		}

		$items = array();

		foreach ( $gateways as $gateway ) {
			if ( $gateway instanceof \WC_Payment_Gateway ) {
				$items[] = array(
					'id'           => (string) $gateway->id,
					'title'        => (string) $gateway->get_title(),
					'method_title' => (string) $gateway->get_method_title(),
					'description'  => (string) $gateway->get_description(),
					'enabled'      => wc_string_to_bool( $gateway->enabled ),
				);
			}
		}

		return array(
			'gateways'    => $items,
			'total_pages' => $items ? 1 : 0,
			'page'        => 1,
			'per_page'    => count( $items ),
		);
	}

	/**
	 * Same check as the payment gateways REST endpoint.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_query_payment_gateways(): bool {
		return wc_rest_check_manager_permissions( 'payment_gateways', 'read' );
	}

	/**
	 * Optional gateway ID; no paging, since stores have a handful of gateways.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id' => array(
					'type'        => 'string',
					'description' => __( 'Payment gateway ID, such as bacs or stripe.', 'woocommerce' ),
					'minLength'   => 1,
				),
			),
			'default'              => array(),
			'additionalProperties' => false,
		);
	}

	/**
	 * Closed item shape, so no settings field can be added to the output.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return self::get_collection_output_schema(
			'gateways',
			array(
				'type'                 => 'object',
				'properties'           => array(
					'id'           => array( 'type' => 'string' ),
					'title'        => array(
						'type'        => 'string',
						'description' => __( 'Title shown to customers at checkout.', 'woocommerce' ),
					),
					'method_title' => array(
						'type'        => 'string',
						'description' => __( 'Name of the payment method or provider.', 'woocommerce' ),
					),
					'description'  => array(
						'type'        => 'string',
						'description' => __( 'Description shown to customers at checkout.', 'woocommerce' ),
					),
					'enabled'      => array( 'type' => 'boolean' ),
				),
				'additionalProperties' => false,
			)
		);
	}
}
