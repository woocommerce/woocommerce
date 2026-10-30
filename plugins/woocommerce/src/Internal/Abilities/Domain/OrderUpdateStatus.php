<?php
/**
 * Order update status ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce order update status ability. It runs the steps of
 * ActionableAbility. With the feature on, change() stages the status with
 * WC_Order::stage_status(), so validators see the new status before the save.
 * With the feature off, save() calls WC_Order::update_status() as before.
 */
class OrderUpdateStatus extends ActionableAbility {

	use DomainAbilityHelpers;
	use OrderAbilityTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/order-update-status';

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Update order status', 'woocommerce' ),
			'description'         => __(
				'Update an order status.',
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
	public function load( array $input ) {
		return self::get_order_from_input( $input );
	}

	/**
	 * Check the new status. With the feature on, stage it with
	 * WC_Order::stage_status(), or refuse the change when the order class cannot
	 * stage it. With the feature off, no field or validator runs before save(),
	 * so this step changes the status with WC_Order::update_status(), which
	 * also saves, and save() does nothing.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return null|\WP_Error
	 */
	public function change( $subject, array $input ) {
		if ( empty( $input['status'] ) ) {
			return new \WP_Error(
				'woocommerce_order_status_required',
				__( 'Order status is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$status = OrderUtil::remove_status_prefix( sanitize_key( $input['status'] ) );

		if ( ! in_array( $status, self::get_query_order_status_slugs(), true ) ) {
			return new \WP_Error(
				'woocommerce_order_status_invalid',
				__( 'Order status is invalid.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( $status === $subject->get_status() ) {
			return new \WP_Error(
				'woocommerce_order_status_unchanged',
				__(
					'Order already has this status. Use the woocommerce/order-add-note ability to add a note without changing status.',
					'woocommerce'
				),
				array( 'status' => 400 )
			);
		}

		if ( ! $subject->is_valid_status( $status ) ) {
			return new \WP_Error(
				'woocommerce_order_status_invalid',
				/* translators: %s: order status slug. */
				sprintf( __( 'This order cannot have the "%s" status.', 'woocommerce' ), $status ),
				array( 'status' => 400 )
			);
		}

		if ( ! ExtensibleAbilities::is_enabled() ) {
			return self::update_status( $subject, $status, self::get_note( $input ) );
		}

		try {
			$subject->stage_status( $status, self::get_note( $input ), true );
		} catch ( \WC_Data_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'woocommerce_order_status_update_failed',
				$e->getMessage(),
				array( 'status' => 400 )
			);
		}

		return null;
	}

	/**
	 * Save the order. With the feature off, change() already saved it through
	 * WC_Order::update_status().
	 *
	 * @param \WC_Order $subject Order.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		return ExtensibleAbilities::is_enabled() ? parent::save( $subject ) : null;
	}

	/**
	 * Change the status with WC_Order::update_status(), as a manual change. The
	 * order's own rules run, and the order is saved.
	 *
	 * @param \WC_Order $order  Order.
	 * @param string    $status New status, without the wc- prefix.
	 * @param string    $note   Status change note.
	 * @return null|\WP_Error
	 */
	private static function update_status( \WC_Order $order, string $status, string $note ): ?\WP_Error {
		try {
			$updated = $order->update_status( $status, $note, true );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'woocommerce_order_status_update_failed',
				$e->getMessage(),
				array( 'status' => 400 )
			);
		}

		if ( ! $updated ) {
			return new \WP_Error(
				'woocommerce_order_status_update_failed',
				__( 'Failed to update order status.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return null;
	}

	/**
	 * The ability output for the saved order.
	 *
	 * @param \WC_Order $subject Saved order.
	 * @return array
	 */
	public function prepare_response( $subject ): array {
		return array(
			'order' => self::format_order_for_response( $subject, false ),
		);
	}

	/**
	 * Get the status change note from the input.
	 *
	 * @param array $input Ability input.
	 * @return string
	 */
	private static function get_note( array $input ): string {
		return isset( $input['note'] ) ? wp_kses_post( $input['note'] ) : '';
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
				'id'     => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'status' => array(
					'type'        => 'string',
					'description' => __( 'Order status slug without the wc- prefix. Custom order types may add their own statuses.', 'woocommerce' ),
					'enum'        => self::get_query_order_status_slugs(),
				),
				'note'   => array(
					'type'        => 'string',
					'description' => __( 'Optional status change note. Safe HTML is allowed. Use the woocommerce/order-add-note ability for notes without a status change.', 'woocommerce' ),
				),
			),
			'required'             => array( 'id', 'status' ),
			'additionalProperties' => false,
		);
	}
}
