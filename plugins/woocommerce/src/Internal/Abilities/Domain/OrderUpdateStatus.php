<?php
/**
 * Order update status ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\AbilityFields;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;
use Automattic\WooCommerce\Internal\AbilitiesApi\SideEffectGuard;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce order update status ability.
 */
class OrderUpdateStatus extends AbstractChangeAbility implements AbilityDefinition {

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
	 * Object type that the ability changes.
	 *
	 * @return string
	 */
	public static function get_object_type(): string {
		return 'order';
	}

	/**
	 * Load the order to change.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	public static function load( array $input ) {
		return self::get_order_from_input( $input );
	}

	/**
	 * Change the order status in memory.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return null|\WP_Error
	 */
	public static function change( $subject, array $input ) {
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

		$subject->set_status(
			$status,
			isset( $input['note'] ) ? wp_kses_post( $input['note'] ) : '',
			true
		);

		foreach ( array( 'date_paid', 'date_completed' ) as $date ) {
			if ( array_key_exists( $date, $input ) ) {
				$subject->{"set_{$date}"}( $input[ $date ] );
			}
		}

		return null;
	}

	/**
	 * Save the order, as WC_Order::update_status() does.
	 *
	 * @param \WC_Order $subject Changed order.
	 * @return null|\WP_Error
	 */
	public static function save( $subject ) {
		try {
			$subject->save();
		} catch ( \Exception $e ) {
			wc_get_logger()->error(
				sprintf( 'Error updating status for order #%d', $subject->get_id() ),
				array(
					'order' => $subject,
					'error' => $e,
				)
			);
			$subject->add_order_note( __( 'Update status event failed.', 'woocommerce' ) . ' ' . $e->getMessage(), false, false, array( 'note_group' => OrderNoteGroup::ERROR ) );

			return new \WP_Error(
				'woocommerce_order_status_update_failed',
				__( 'Failed to update order status.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return null;
	}

	/**
	 * The status update that sets the status and the dates that a status change sets back.
	 *
	 * @param \WC_Order $subject Order before the change.
	 * @param array     $input   Ability input.
	 * @return array{ability: string, input: array}
	 */
	public static function undo( $subject, array $input ): ?array {
		$date_paid      = $subject->get_date_paid( 'edit' );
		$date_completed = $subject->get_date_completed( 'edit' );

		return array(
			'ability' => self::get_name(),
			'input'   => array(
				'id'             => $subject->get_id(),
				'status'         => $subject->get_status(),
				'date_paid'      => null === $date_paid ? null : (string) $date_paid,
				'date_completed' => null === $date_completed ? null : (string) $date_completed,
			),
		);
	}

	/**
	 * The enabled emails that the status change sends.
	 *
	 * @param \WC_Order $subject Order before the change.
	 * @param array     $input   Ability input.
	 * @return array<int, array{code: string, value: mixed, description: string}>
	 */
	public static function side_effects( $subject, array $input ): array {
		$to    = OrderUtil::remove_status_prefix( sanitize_key( (string) ( $input['status'] ?? '' ) ) );
		$hooks = array(
			"woocommerce_order_status_{$subject->get_status()}_to_{$to}_notification",
			"woocommerce_order_status_{$to}_notification",
		);

		$effects = array();
		foreach ( WC()->mailer()->get_emails() as $email ) {
			if ( ! $email->is_enabled() ) {
				continue;
			}
			foreach ( $hooks as $hook ) {
				if ( false !== has_action( $hook, array( $email, 'trigger' ) ) ) {
					$effects[] = SideEffectGuard::side_effect(
						'email',
						$email->id,
						sprintf(
							$email->is_customer_email()
								/* translators: %s: Email title, such as Completed order. */
								? __( 'Sends the "%s" email to the customer.', 'woocommerce' )
								/* translators: %s: Email title, such as New order. */
								: __( 'Sends the "%s" email to the store.', 'woocommerce' ),
							$email->get_title()
						)
					);
					break;
				}
			}
		}
		return $effects;
	}

	/**
	 * The ability output for the saved order.
	 *
	 * @param \WC_Order $subject Saved order.
	 * @return array
	 */
	public static function prepare_response( $subject ): array {
		return array(
			'order' => self::format_order_for_response( $subject, false ),
		);
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'id'     => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'status' => array(
					'title'       => __( 'Status', 'woocommerce' ),
					'type'        => 'string',
					'description' => __( 'Order status slug without the wc- prefix.', 'woocommerce' ),
					'enum'        => self::get_allowed_order_status_slugs(),
				),
				'note'   => array(
					'title'       => __( 'Note', 'woocommerce' ),
					'type'        => 'string',
					'description' => __( 'Optional status change note. Safe HTML is allowed. Use the woocommerce/order-add-note ability for notes without a status change.', 'woocommerce' ),
				),
			),
			'required'             => array( 'id', 'status' ),
			'additionalProperties' => false,
		);
		if ( AbilityContracts::is_enabled() ) {
			$schema['properties']['date_paid']      = array(
				'title'       => __( 'Date paid', 'woocommerce' ),
				'type'        => array( 'string', 'null' ),
				'format'      => 'date-time',
				'description' => __( 'Optional. Date the order was paid, or null for none. The undo of a status change sets it back.', 'woocommerce' ),
			);
			$schema['properties']['date_completed'] = array(
				'title'       => __( 'Date completed', 'woocommerce' ),
				'type'        => array( 'string', 'null' ),
				'format'      => 'date-time',
				'description' => __( 'Optional. Date the order was completed, or null for none. The undo of a status change sets it back.', 'woocommerce' ),
			);
		}
		return AbilityFields::add_to_input_schema( $schema, 'order' );
	}
}
