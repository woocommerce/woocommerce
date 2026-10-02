<?php
/**
 * Order update status ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce order update status ability.
 */
class OrderUpdateStatus extends AbstractDomainAbility implements AbilityDefinition {

	use OrderAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function get_name(): string {
		return 'woocommerce/order-update-status';
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
			'label'               => __( 'Update order status', 'woocommerce' ),
			'description'         => __(
				'Update an order status.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'order', self::get_order_output_schema() ),
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
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Update an order status.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute( array $input ) {
		$order = self::load( $input );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$status = self::get_status_from_input( $order, $input );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$updated = $order->update_status(
			$status,
			isset( $input['note'] ) ? wp_kses_post( $input['note'] ) : '',
			true
		);

		if ( ! $updated ) {
			return new \WP_Error(
				'woocommerce_order_status_update_failed',
				__( 'Failed to update order status.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return self::respond( $order );
	}

	/**
	 * Load the order. Never saves.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	public static function load( array $input ) {
		return self::get_order_from_input( $input );
	}

	/**
	 * Set the status in memory. The save runs the status transition: the note,
	 * the status hooks and the emails.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $input Ability input.
	 * @return null|\WP_Error
	 */
	public static function change( \WC_Order $order, array $input ) {
		$status = self::get_status_from_input( $order, $input );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		$order->set_status( $status, isset( $input['note'] ) ? wp_kses_post( $input['note'] ) : '', true );
		return null;
	}

	/**
	 * The enabled emails the status change sends.
	 *
	 * @param \WC_Order $order Order, before the change.
	 * @param array     $input Ability input.
	 * @return string[]
	 */
	public static function side_effects( \WC_Order $order, array $input ): array {
		$to    = OrderUtil::remove_status_prefix( sanitize_key( (string) ( $input['status'] ?? '' ) ) );
		$hooks = array(
			"woocommerce_order_status_{$order->get_status()}_to_{$to}_notification",
			"woocommerce_order_status_{$to}_notification",
		);

		$effects = array();
		foreach ( WC()->mailer()->get_emails() as $email ) {
			if ( ! $email->is_enabled() ) {
				continue;
			}
			foreach ( $hooks as $hook ) {
				if ( false !== has_action( $hook, array( $email, 'trigger' ) ) ) {
					$effects[] = sprintf(
						$email->is_customer_email()
							/* translators: %s: email title, such as Completed order. */
							? __( 'Sends the "%s" email to the customer.', 'woocommerce' )
							/* translators: %s: email title, such as New order. */
							: __( 'Sends the "%s" email to the store.', 'woocommerce' ),
						$email->get_title()
					);
					break;
				}
			}
		}
		return $effects;
	}

	/**
	 * The status update that puts the previous status back.
	 *
	 * @param \WC_Order $order Order, before the change.
	 * @param array     $input Ability input.
	 * @return array{ability: string, input: array}
	 */
	public static function undo( \WC_Order $order, array $input ): array {
		return array(
			'ability' => self::get_name(),
			'input'   => array(
				'id'     => $order->get_id(),
				'status' => $order->get_status(),
			),
		);
	}

	/**
	 * The response for the saved order.
	 *
	 * @param \WC_Order $order Saved order.
	 * @return array
	 */
	public static function respond( \WC_Order $order ): array {
		return array(
			'order' => self::format_order_for_response( $order, false ),
		);
	}

	/**
	 * The status the input asks for, or why it cannot be set.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $input Ability input.
	 * @return string|\WP_Error
	 */
	private static function get_status_from_input( \WC_Order $order, array $input ) {
		if ( empty( $input['status'] ) ) {
			return new \WP_Error(
				'woocommerce_order_status_required',
				__( 'Order status is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$status = OrderUtil::remove_status_prefix( sanitize_key( $input['status'] ) );

		if ( ! in_array( $status, self::get_allowed_order_status_slugs(), true ) ) {
			return new \WP_Error(
				'woocommerce_order_status_invalid',
				__( 'Order status is invalid.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( $status === $order->get_status() ) {
			return new \WP_Error(
				'woocommerce_order_status_unchanged',
				__(
					'Order already has this status. Use the woocommerce/order-add-note ability to add a note without changing status.',
					'woocommerce'
				),
				array( 'status' => 400 )
			);
		}

		return $status;
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
				'id'     => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'status' => array(
					'type'        => 'string',
					'description' => __( 'Order status slug without the wc- prefix.', 'woocommerce' ),
					'enum'        => self::get_allowed_order_status_slugs(),
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
