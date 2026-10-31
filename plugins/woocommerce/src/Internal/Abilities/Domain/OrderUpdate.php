<?php
/**
 * Order update ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce order update ability. It runs the steps of ActionableAbility.
 */
class OrderUpdate extends ActionableAbility {

	use DomainAbilityHelpers;
	use OrderAbilityTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/order-update';

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Update order', 'woocommerce' ),
			'description'         => __(
				'Update an existing order using supported order fields. Use the woocommerce/order-update-status ability to change the order status.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'order', self::get_order_output_schema() ),
			'permission_callback' => array( __CLASS__, 'can_edit_order' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'order';
	}

	/**
	 * Load the order to change.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	protected function load( array $input ) {
		$order = self::get_order_from_input( $input );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		if ( empty( array_diff( array_keys( $input ), array( 'id' ) ) ) ) {
			return new \WP_Error(
				'woocommerce_order_update_no_fields',
				__( 'At least one order field is required to update an order.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return $order;
	}

	/**
	 * Set the order properties in memory.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return null|\WP_Error
	 */
	protected function change( $subject, array $input ) {
		if ( ! isset( $input['shipping'] ) ) {
			return null;
		}

		$shipping = $input['shipping'];

		if ( isset( $shipping['country'] ) && '' !== $shipping['country'] && ! array_key_exists( $shipping['country'], WC()->countries->get_countries() ) ) {
			return new \WP_Error(
				'woocommerce_order_shipping_country_invalid',
				__( 'The shipping country is not a valid country code.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		try {
			foreach ( $shipping as $key => $value ) {
				$subject->{"set_shipping_{$key}"}( wc_clean( $value ) );
			}
		} catch ( \WC_Data_Exception $exception ) {
			return new \WP_Error( $exception->getErrorCode(), $exception->getMessage(), array( 'status' => 400 ) );
		}

		$country  = $subject->get_shipping_country();
		$state    = $subject->get_shipping_state();
		$postcode = $subject->get_shipping_postcode();
		$states   = '' === $country ? array() : WC()->countries->get_states( $country );

		if ( '' !== $state && is_array( $states ) && ! empty( $states ) && ! array_key_exists( $state, $states ) ) {
			return new \WP_Error(
				'woocommerce_order_shipping_state_invalid',
				__( 'The shipping state is not a valid state code for the shipping country.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( '' !== $postcode && '' !== $country ) {
			if ( ! \WC_Validation::is_postcode( $postcode, $country ) ) {
				return new \WP_Error(
					'woocommerce_order_shipping_postcode_invalid',
					__( 'The shipping postcode is not valid for the shipping country.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$subject->set_shipping_postcode( wc_format_postcode( $postcode, $country ) );
		}

		return null;
	}

	/**
	 * The ability output for the saved order.
	 *
	 * @param \WC_Order $subject Saved order.
	 * @return array
	 */
	protected function prepare_response( $subject ): array {
		return array(
			'order' => self::format_order_for_response( $subject, false ),
		);
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function build_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'       => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'shipping' => array(
					'type'                 => 'object',
					'description'          => __( 'Shipping address fields to change. Fields that are not sent keep their value. Use ISO 3166-1 alpha-2 country codes and WooCommerce state codes.', 'woocommerce' ),
					'properties'           => array_fill_keys(
						self::get_order_shipping_address_fields(),
						array( 'type' => 'string' )
					),
					'additionalProperties' => false,
					'minProperties'        => 1,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}
}
