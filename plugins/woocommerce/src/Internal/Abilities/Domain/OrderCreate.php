<?php
/**
 * Order create ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderEditTrait;
use Automattic\WooCommerce\Internal\Utilities\Users;

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce order create ability. The order starts at pending and moves to the sent or default
 * status on its save, so the status hooks run as on the order screen: a paid status reduces the stock
 * and sends the emails.
 */
class OrderCreate extends ActionableAbility {

	use DomainAbilityHelpers;
	use OrderAbilityTrait;
	use OrderEditTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/order-create';

	/**
	 * The created_via value of the orders that this ability creates.
	 */
	public const CREATED_VIA = 'ability';

	/**
	 * Status that the input sent, or the default status. With the feature off, save() applies it once the order
	 * has an ID.
	 *
	 * @var string
	 */
	private string $status = '';

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Create order', 'woocommerce' ),
			'description'         => __(
				'Create an order, as the order screen does, for example for a phone order. Without customer_id, the order is a guest order. Without status, the order gets the default status of the store, which is pending payment unless an extension changes it. A processing, on-hold or completed status reduces the stock of the products and sends the order emails to the store and the customer, so confirm the status before you send it.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'order', self::get_order_output_schema() ),
			'permission_callback' => array( __CLASS__, 'can_create_order' ),
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
	 * Check that the user may create orders.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_create_order(): bool {
		return wc_rest_check_post_permissions( 'shop_order', 'create' );
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
	 * Build an order in memory with the store defaults that wc_create_order() sets.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order
	 */
	public function load( array $input ) {
		$order = new \WC_Order();
		$order->set_created_via( self::CREATED_VIA );
		$order->set_currency( get_woocommerce_currency() );
		$order->set_prices_include_tax( wc_prices_include_tax() );

		return $order;
	}

	/**
	 * Set the customer, addresses and lines in memory, then the status. Every refusal comes back in one error.
	 *
	 * @param \WC_Order $subject New order.
	 * @param array     $input   Ability input.
	 * @return null|\WP_Error
	 * @throws \WC_Data_Exception When the order class has not adopted a stage method.
	 */
	public function change( $subject, array $input ) {
		$this->reset_line_changes();
		$this->status = $input['status'] ?? $subject->get_status();
		// Start at pending without recording a transition, so a filtered default such as on-hold still runs its status hooks.
		$subject->set_object_read( false );
		$subject->set_status( OrderStatus::PENDING );
		$subject->set_object_read( true );

		$refused = new \WP_Error();
		if ( isset( $input['customer_id'] ) ) {
			if ( is_wp_error( Users::get_user_in_current_site( $input['customer_id'] ) ) ) {
				$refused->add(
					'woocommerce_ability_order_customer_not_found',
					/* translators: %d: user ID. */
					sprintf( __( 'User %d is not a user of this site. Send the ID of a customer of this site, or leave out customer_id for a guest order.', 'woocommerce' ), $input['customer_id'] ),
					array( 'status' => 400 )
				);
			} else {
				$subject->set_customer_id( $input['customer_id'] );
			}
		}

		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			$error = isset( $input[ $address_type ] ) ? self::set_address( $subject, $address_type, $input[ $address_type ] ) : null;
			if ( is_wp_error( $error ) ) {
				$refused->merge_from( $error );
			}
		}

		if ( isset( $input['customer_note'] ) ) {
			$subject->set_customer_note( sanitize_textarea_field( $input['customer_note'] ) );
		}

		foreach ( $input['shipping_lines'] ?? array() as $line ) {
			$error = self::set_shipping_line( $subject, $line );
			if ( is_wp_error( $error ) ) {
				$refused->merge_from( $error );
			}
		}

		foreach ( $input['line_items'] ?? array() as $line ) {
			$error = $this->add_product_line( $subject, $line );
			if ( is_wp_error( $error ) ) {
				$refused->merge_from( $error );
			}
		}

		if ( $refused->has_errors() ) {
			return $refused;
		}

		if ( ! empty( $input['line_items'] ) || ! empty( $input['shipping_lines'] ) ) {
			$this->stage_line_totals( $subject, ! empty( $input['line_items'] ) );
		}

		// Not a manual change, as in REST and wc_create_order(): a new order has no ID for woocommerce_order_edit_status.
		if ( ExtensibleAbilities::is_enabled() && $subject->get_status() !== $this->status ) {
			$subject->stage_status( $this->status );
		}

		return null;
	}

	/**
	 * Save the order. With the feature off, the order saves as pending with its lines, is recalculated with the
	 * methods that save, and then moves to its status with WC_Order::update_status(), which needs an order ID.
	 *
	 * @param \WC_Order $subject New order.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		if ( ExtensibleAbilities::is_enabled() ) {
			return parent::save( $subject );
		}

		$added = $this->add_queued_products( $subject );
		$saved = parent::save( $subject );
		if ( is_wp_error( $saved ) ) {
			foreach ( $added as $item ) {
				$item->delete();
			}
			return $saved;
		}

		$recalculated = $this->recalculate_saved_order( $subject );
		if ( is_wp_error( $recalculated ) ) {
			foreach ( $added as $item ) {
				$item->delete();
			}
			return $recalculated;
		}

		if ( $subject->get_status() === $this->status ) {
			return null;
		}

		// The error rolls back the pending order that parent::save() wrote.
		return $subject->update_status( $this->status ) ? null : new \WP_Error(
			'woocommerce_order_status_update_failed',
			__( 'The order was not created, because its status could not be set.', 'woocommerce' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * The ability output for the saved order. It includes the lines, so the client gets their IDs.
	 *
	 * @param \WC_Order $subject Saved order.
	 * @return array|\WP_Error
	 */
	public function prepare_response( $subject ) {
		$stored = wc_get_order( $subject->get_id() );
		$order  = self::format_order_for_response( $stored instanceof \WC_Order ? $stored : $subject, array( 'line_items', 'lines' ) );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		return array( 'order' => $order );
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
				'customer_id'    => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'status'         => array(
					'type'        => 'string',
					'description' => __( 'Order status slug without the wc- prefix. Defaults to the default status of the store.', 'woocommerce' ),
					'enum'        => self::get_allowed_order_status_slugs(),
				),
				'billing'        => self::get_address_input_schema( array_merge( self::shipping_address_fields(), array( 'email' ) ) ),
				'shipping'       => self::get_address_input_schema( self::shipping_address_fields() ),
				'customer_note'  => array( 'type' => 'string' ),
				'line_items'     => array(
					'type'        => 'array',
					'description' => __( 'Product lines. A variation uses its own ID as product_id. The price is the current price of the product.', 'woocommerce' ),
					'minItems'    => 1,
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'product_id' => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'quantity'   => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
						),
						'required'             => array( 'product_id' ),
						'additionalProperties' => false,
					),
				),
				'shipping_lines' => array(
					'type'     => 'array',
					'minItems' => 1,
					'items'    => array(
						'type'                 => 'object',
						'properties'           => array(
							'method_id'    => array( 'type' => 'string' ),
							'method_title' => array( 'type' => 'string' ),
							'total'        => array( 'type' => 'string' ),
						),
						'required'             => array( 'method_title' ),
						'additionalProperties' => false,
					),
				),
			),
			'required'             => array( 'line_items' ),
			'additionalProperties' => false,
		);
	}
}
