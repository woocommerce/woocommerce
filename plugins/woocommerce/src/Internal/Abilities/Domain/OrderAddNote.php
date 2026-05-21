<?php
/**
 * Order add note ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce order add note ability.
 */
class OrderAddNote extends AbstractDomainAbility implements AbilityDefinition {

	use OrderAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function get_name(): string {
		return 'woocommerce/order-add-note';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Add order note', 'woocommerce' ),
			'description'         => __(
				'Add a note to an order.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_order_note_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
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
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Add an order note.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute( array $input ) {
		$order = self::get_order_from_input( $input );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$note = isset( $input['note'] ) ? trim( wp_kses_post( (string) $input['note'] ) ) : '';

		if ( '' === $note ) {
			return new \WP_Error(
				'woocommerce_order_note_required',
				__( 'Order note is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$note_id = $order->add_order_note(
			$note,
			( (bool) ( $input['customer_note'] ?? false ) ) ? 1 : 0,
			get_current_user_id() > 0
		);

		if ( $note_id <= 0 ) {
			return new \WP_Error(
				'woocommerce_order_note_create_failed',
				__( 'Failed to add order note.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'note_id' => (int) $note_id,
			'order'   => self::format_order_for_response( $order, false ),
		);
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'            => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'note'          => array(
					'type'        => 'string',
					'description' => __( 'Order note content. Safe HTML is allowed.', 'woocommerce' ),
					'minLength'   => 1,
					'pattern'     => '\S',
				),
				'customer_note' => array(
					'type'        => 'boolean',
					'description' => __(
						'Whether the note is visible to the customer. Defaults to false for a private/admin note.',
						'woocommerce'
					),
					'default'     => false,
				),
			),
			'required'             => array( 'id', 'note' ),
			'additionalProperties' => false,
		);
	}
}
