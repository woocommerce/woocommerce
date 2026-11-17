<?php
/**
 * Payment gateways query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\PaymentGatewayAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Lists payment gateways from their public properties, never their settings.
 */
class PaymentGatewaysQuery extends AbstractDomainAbility implements AbilityDefinition {

	use PaymentGatewayAbilityTrait;

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
		$gateways = WC()->payment_gateways()->payment_gateways();

		if ( isset( $input['id'] ) && ! isset( $gateways[ $input['id'] ] ) ) {
			return self::payment_gateway_not_found( 'woocommerce_payment_gateway_not_found' );
		}

		$items = array();
		$order = 0;

		foreach ( $gateways as $id => $gateway ) {
			if ( ! isset( $input['id'] ) || $input['id'] === (string) $id ) {
				$items[] = self::format_payment_gateway_for_response( $gateway, $order );
			}
			++$order;
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
		return (bool) wc_rest_check_manager_permissions( 'payment_gateways', 'read' );
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
					'description' => __( 'Payment gateway ID, such as bacs or cod.', 'woocommerce' ),
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
		return self::get_collection_output_schema( 'gateways', self::get_payment_gateway_item_schema() );
	}
}
