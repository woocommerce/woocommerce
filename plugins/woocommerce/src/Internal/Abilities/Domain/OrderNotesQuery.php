<?php
/**
 * Order notes query ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce order notes query ability.
 */
class OrderNotesQuery extends AbstractDomainAbility implements AbilityDefinition {

	use OrderAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 11.3.0
	 */
	public static function get_name(): string {
		return 'woocommerce/order-notes-query';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.3.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Query order notes', 'woocommerce' ),
			'description'         => __(
				'List every note of an order, newest first.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_output_schema(),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_view_order' ),
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
	 * List the notes of an order.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 11.3.0
	 */
	public static function execute( array $input ) {
		$order = self::get_order_from_input( $input );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'orderby'  => 'date_created_gmt',
				'order'    => 'DESC',
			)
		);

		return array(
			'notes' => array_map(
				static function ( $note ): array {
					return array(
						'id'            => (int) $note->id,
						'author'        => (string) $note->added_by,
						'date_created'  => $note->date_created ? $note->date_created->format( 'c' ) : null,
						'note'          => (string) $note->content,
						'customer_note' => (bool) $note->customer_note,
					);
				},
				$notes
			),
		);
	}

	/**
	 * Check order read access for an ability input payload.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 11.3.0
	 */
	public static function can_view_order( $input = array() ): bool {
		$order_id = self::get_id_from_input( $input );

		return $order_id > 0 && self::check_order_permission( $order_id, 'read' );
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
				'id' => array(
					'type'        => 'integer',
					'description' => __( 'Order ID.', 'woocommerce' ),
					'minimum'     => 1,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the ability output schema.
	 *
	 * @return array
	 */
	private static function get_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'notes' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'            => array( 'type' => 'integer' ),
							'author'        => array( 'type' => 'string' ),
							'date_created'  => array(
								'type'   => array( 'string', 'null' ),
								'format' => 'date-time',
							),
							'note'          => array( 'type' => 'string' ),
							'customer_note' => array( 'type' => 'boolean' ),
						),
						'additionalProperties' => false,
					),
				),
			),
			'additionalProperties' => false,
		);
	}
}
