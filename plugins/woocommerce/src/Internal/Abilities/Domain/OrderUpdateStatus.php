<?php
/**
 * Order update status ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\StagedMethod;
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
	 * stage it. With the feature off, change nothing: save() calls
	 * WC_Order::update_status().
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

		$status = self::get_new_status( $input );

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
			return null;
		}

		$staged = new StagedMethod( 'update_status', 'stage_status' );
		if ( ! $staged->supports( $subject ) ) {
			return $staged->unsupported_error(
				'woocommerce_order_status_staging_unsupported',
				__( 'This order type does not support this change yet.', 'woocommerce' )
			);
		}

		try {
			$subject->stage_status( $status, self::get_note( $input ), true );
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
	 * Save the order. With the feature on, the status is already staged, so the
	 * default save writes it with the extension fields. With the feature off,
	 * change the status with WC_Order::update_status(), as a manual change.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return null|\WP_Error
	 */
	public function save( $subject, array $input = array() ) {
		if ( ExtensibleAbilities::is_enabled() ) {
			return parent::save( $subject, $input );
		}

		try {
			$updated = $subject->update_status( self::get_new_status( $input ), self::get_note( $input ), true );
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
	 * Get the new status from the input, without the wc- prefix.
	 *
	 * @param array $input Ability input.
	 * @return string
	 */
	private static function get_new_status( array $input ): string {
		return OrderUtil::remove_status_prefix( sanitize_key( $input['status'] ?? '' ) );
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
