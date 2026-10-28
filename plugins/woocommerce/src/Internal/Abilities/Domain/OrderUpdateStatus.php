<?php
/**
 * Order update status ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Resources\OrderResource;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce order update status ability.
 */
class OrderUpdateStatus extends ActionableAbility {

	use DomainAbilityHelpers;
	use OrderAbilityTrait;

	public const ABILITY_NAME = 'woocommerce/order-update-status';

	/**
	 * What change() hands to save(), keyed by order object, so another run of the ability in between cannot overwrite it.
	 *
	 * @var array<int, array{status: string, date_paid: ?int, date_completed: ?int, new_status: string, note: string}>
	 */
	private array $staged = array();

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
			'output_schema'       => self::get_entity_output_schema( 'order', AbilityResources::output_schema( OrderResource::NAME, null, array( 'line_items' ) ) ),
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
	 * Resource the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return OrderResource::NAME;
	}

	/**
	 * Load the order.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Data|\WP_Error
	 */
	public function load( array $input ) {
		return self::get_order_from_input( $input );
	}

	/**
	 * Set the new status so validators see it, without the transition hooks that save() runs.
	 *
	 * @param \WC_Order $subject Order to change.
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

		$paid                                      = $subject->get_date_paid( 'edit' );
		$completed                                 = $subject->get_date_completed( 'edit' );
		$this->staged[ spl_object_id( $subject ) ] = array(
			'status'         => $subject->get_status(),
			'date_paid'      => $paid ? $paid->getTimestamp() : null,
			'date_completed' => $completed ? $completed->getTimestamp() : null,
			'new_status'     => $status,
			'note'           => isset( $input['note'] ) ? wp_kses_post( $input['note'] ) : '',
		);
		$subject->set_status( $status );
		return null;
	}

	/**
	 * Save through WC_Order::update_status(), which order classes can override.
	 *
	 * @param \WC_Order $subject Changed order.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		$staged = $this->staged[ spl_object_id( $subject ) ] ?? null;
		unset( $this->staged[ spl_object_id( $subject ) ] );
		if ( null === $staged ) {
			return new \WP_Error( 'woocommerce_order_status_update_failed', __( 'Failed to update order status.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		// Restore the loaded order, so update_status() runs the transition from the original status.
		$subject->set_status( $staged['status'] );
		$subject->set_date_paid( $staged['date_paid'] );
		$subject->set_date_completed( $staged['date_completed'] );

		try {
			$updated = $subject->update_status( $staged['new_status'], $staged['note'], true );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'woocommerce_order_status_update_failed', $e->getMessage(), array( 'status' => 400 ) );
		}

		if ( ! $updated ) {
			return new \WP_Error( 'woocommerce_order_status_update_failed', __( 'Failed to update order status.', 'woocommerce' ), array( 'status' => 500 ) );
		}
		return null;
	}

	/**
	 * The ability output for the saved order. Line items are left out, like in its output schema.
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
