<?php
/**
 * Order update status ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\AbilitiesApi\ObjectChangeAbility;

defined( 'ABSPATH' ) || exit;

/**
 * Runs the order status update ability as an object change, with the steps
 * OrderUpdateStatus provides.
 *
 * @since 11.3.0
 */
final class OrderUpdateStatusAbility extends ObjectChangeAbility {

	/**
	 * Object type.
	 */
	public static function object_type(): string {
		return 'order';
	}

	/**
	 * Load the order. Never saves.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	public function load( array $input ) {
		return OrderUpdateStatus::load( $input );
	}

	/**
	 * Set the status in memory. Never saves.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return null|\WP_Error
	 */
	public function change( $subject, array $input ) {
		return OrderUpdateStatus::change( $subject, $input );
	}

	/**
	 * The response for the saved order.
	 *
	 * @param \WC_Order $subject Saved order.
	 * @return array
	 */
	public function prepare_response( $subject ) {
		return OrderUpdateStatus::respond( $subject );
	}

	/**
	 * The status update that puts the previous status back.
	 *
	 * @param \WC_Order $subject Order, before the change.
	 * @param array     $input   Ability input.
	 * @return array{ability: string, input: array}
	 */
	public function undo( $subject, array $input ): array {
		return OrderUpdateStatus::undo( $subject, $input );
	}

	/**
	 * The enabled emails the status change sends.
	 *
	 * @param \WC_Order $subject Order, before the change.
	 * @param array     $input   Ability input.
	 * @return string[]
	 */
	public function side_effects( $subject, array $input ): array {
		return OrderUpdateStatus::side_effects( $subject, $input );
	}
}
