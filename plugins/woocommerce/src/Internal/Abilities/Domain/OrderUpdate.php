<?php
/**
 * Order update ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderEditTrait;
use Automattic\WooCommerce\Internal\Admin\Orders\ItemQuantityLimits;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce order update ability. It runs the steps of ActionableAbility.
 * Every change stays in memory until save(), so a refusal from a later change
 * or from a validator saves nothing.
 */
class OrderUpdate extends ActionableAbility {

	use DomainAbilityHelpers;
	use OrderAbilityTrait;
	use OrderEditTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/order-update';

	/**
	 * Product lines that change() removed, for the stock change in save().
	 *
	 * @var array<int, \WC_Order_Item_Product>
	 */
	private array $removed_product_lines = array();

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
				'Update the addresses, customer note, product lines and shipping lines of an order. Fields that are not sent keep their value. Changing lines recalculates the totals and needs an order status that allows editing. Use the woocommerce/order-update-status ability to change the status.',
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
		$order = self::get_order_from_input( $input );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		if ( empty( array_diff( array_keys( $input ), array( 'id' ) ) ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_no_fields',
				__( 'No change was sent. Send at least one order field to change, in addition to the id.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return $order;
	}

	/**
	 * Change the order in memory. A refused address or line does not stop the
	 * others, and every refusal comes back in one error.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return null|\WP_Error
	 * @throws \WC_Data_Exception When the order class has not adopted a stage method.
	 */
	public function change( $subject, array $input ) {
		$this->removed_product_lines = array();
		$this->reset_line_changes();

		$refused = new \WP_Error();
		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			$error = isset( $input[ $address_type ] ) ? self::set_address( $subject, $address_type, $input[ $address_type ] ) : null;
			if ( is_wp_error( $error ) ) {
				$refused->merge_from( $error );
			}
		}

		if ( isset( $input['customer_note'] ) ) {
			$subject->set_customer_note( sanitize_textarea_field( $input['customer_note'] ) );
		}

		if ( empty( $input['remove_items'] ) && empty( $input['shipping_lines'] ) && empty( $input['line_items'] ) ) {
			return $refused->has_errors() ? $refused : null;
		}

		if ( ! $subject->is_editable() ) {
			$refused->add(
				'woocommerce_ability_order_not_editable',
				sprintf(
					/* translators: %s: order status slug. */
					__( 'The lines of an order with the "%s" status cannot change. Move the order to an editable status, such as on-hold, with the woocommerce/order-update-status ability, then send the change again.', 'woocommerce' ),
					$subject->get_status()
				),
				array( 'status' => 400 )
			);
			return $refused;
		}

		$error = $this->change_lines( $subject, $input );
		if ( is_wp_error( $error ) ) {
			$refused->merge_from( $error );
		}

		return $refused->has_errors() ? $refused : null;
	}

	/**
	 * Save the order, recalculate it with the feature off, and change the product
	 * stock of the changed lines in the same way as the order screen. Validators have already run, so a refused
	 * change never reaches the stock. A removed line restores its stock before
	 * the save, because the save deletes the line that holds the reduced amount. After a successful save, one
	 * order note lists the stock changes, in the same format as the order screen.
	 *
	 * @param \WC_Order $subject Changed order.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';

		$stock_notes = array();
		foreach ( $this->removed_product_lines as $item ) {
			$changed_stock = wc_maybe_adjust_line_item_product_stock( $item, 0 );
			if ( is_array( $changed_stock ) ) {
				$stock_notes[] = $item->get_name() . ' &ndash; ' . $changed_stock['from'] . '&rarr;' . $changed_stock['to'];
			}
		}

		$added = $this->add_queued_products( $subject );

		$saved = parent::save( $subject );

		if ( is_wp_error( $saved ) ) {
			foreach ( $added as $item ) {
				$item->delete();
			}
			return $saved;
		}

		$this->changed_product_lines = array_merge( $this->changed_product_lines, $added );

		$recalculated = $this->recalculate_saved_order( $subject );
		if ( is_wp_error( $recalculated ) ) {
			foreach ( $added as $item ) {
				$item->delete();
			}
			return $recalculated;
		}

		if ( in_array( $subject->get_status(), array( OrderStatus::PROCESSING, OrderStatus::COMPLETED, OrderStatus::ON_HOLD ), true ) ) {
			foreach ( $this->changed_product_lines as $item ) {
				$changed_stock = wc_maybe_adjust_line_item_product_stock( $item );
				if ( is_array( $changed_stock ) ) {
					$stock_notes[] = $item->get_name() . ' (' . $changed_stock['from'] . '&rarr;' . $changed_stock['to'] . ')';
				}
			}
		}

		if ( ! empty( $stock_notes ) ) {
			/* translators: %s item name. */
			$subject->add_order_note( sprintf( __( 'Adjusted stock: %s', 'woocommerce' ), implode( ', ', $stock_notes ) ), 0, true, array( 'note_group' => OrderNoteGroup::PRODUCT_STOCK ) );
		}

		return null;
	}

	/**
	 * The ability output for the saved order. It includes the lines, so the
	 * client gets the IDs of the lines it added.
	 *
	 * @param \WC_Order $subject Saved order.
	 * @return array|\WP_Error
	 */
	public function prepare_response( $subject ) {
		$order = self::format_order_for_response( $subject, array( 'line_items', 'lines' ) );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		return array( 'order' => $order );
	}

	/**
	 * Remove, change and add lines in memory, then recalculate the totals
	 * without saving. Coupons apply again after a product is added, because
	 * they were applied before the new line existed. Every refused line comes
	 * back in one error, and then nothing is recalculated.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $input Ability input.
	 * @return null|\WP_Error
	 * @throws \WC_Data_Exception When the order class has not adopted a stage method.
	 */
	private function change_lines( \WC_Order $order, array $input ): ?\WP_Error {
		$refused = new \WP_Error();
		foreach ( $input['remove_items'] ?? array() as $item_id ) {
			$item = $order->get_item( $item_id, false );

			if ( ! $item instanceof \WC_Order_Item_Product && ! $item instanceof \WC_Order_Item_Shipping ) {
				$refused->merge_from( self::line_not_found( $item_id ) );
				continue;
			}

			if ( $item instanceof \WC_Order_Item_Product ) {
				$this->removed_product_lines[] = $item;
			}

			$order->remove_item( $item_id );
		}

		foreach ( $input['shipping_lines'] ?? array() as $line ) {
			$error = self::set_shipping_line( $order, $line );
			if ( is_wp_error( $error ) ) {
				$refused->merge_from( $error );
			}
		}

		$product_added = false;

		foreach ( $input['line_items'] ?? array() as $line ) {
			if ( empty( $line['id'] ) === empty( $line['product_id'] ) ) {
				$refused->add(
					'woocommerce_ability_order_line_item_target_invalid',
					__( 'Each entry in line_items needs exactly one target. Send id to change a line, or product_id to add a line.', 'woocommerce' ),
					array( 'status' => 400 )
				);
				continue;
			}

			$error = empty( $line['id'] ) ? $this->add_product_line( $order, $line ) : $this->change_product_line( $order, $line );
			if ( is_wp_error( $error ) ) {
				$refused->merge_from( $error );
				continue;
			}

			$product_added = $product_added || empty( $line['id'] );
		}

		if ( $refused->has_errors() ) {
			return $refused;
		}

		$this->stage_line_totals( $order, $product_added );

		return null;
	}

	/**
	 * Give a product line a new quantity in memory. The subtotal and the total
	 * keep their price per unit, so a custom price or a discount stays.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $line  Product line input.
	 * @return null|\WP_Error
	 */
	private function change_product_line( \WC_Order $order, array $line ): ?\WP_Error {
		$item = $order->get_item( $line['id'], false );

		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return self::line_not_found( $line['id'] );
		}

		if ( ! isset( $line['quantity'] ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_line_item_quantity_required',
				/* translators: %d: order item ID. */
				sprintf( __( 'Nothing to change on line %d. Send quantity to change the line, or send its ID in remove_items to remove it.', 'woocommerce' ), $line['id'] ),
				array( 'status' => 400 )
			);
		}

		try {
			wc_get_container()->get( ItemQuantityLimits::class )->validate_posted_item_quantities(
				$order,
				array( 'order_item_qty' => array( $line['id'] => $line['quantity'] ) )
			);
		} catch ( \Exception $exception ) {
			return self::quantity_error( $exception );
		}

		$old_quantity = (float) $item->get_quantity();
		$ratio        = $old_quantity > 0 ? $line['quantity'] / $old_quantity : 1;

		$item->set_subtotal( (string) ( (float) $item->get_subtotal() * $ratio ) );
		$item->set_total( (string) ( (float) $item->get_total() * $ratio ) );
		$item->set_quantity( $line['quantity'] );

		$this->changed_product_lines[] = $item;

		return null;
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
				'id'             => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'billing'        => self::get_address_input_schema( array_merge( self::shipping_address_fields(), array( 'email' ) ) ),
				'shipping'       => self::get_address_input_schema( self::shipping_address_fields() ),
				'customer_note'  => array( 'type' => 'string' ),
				'line_items'     => array(
					'type'        => 'array',
					'description' => __( 'Send id and quantity to change a line, or product_id and quantity to add one. A variation uses its own ID as product_id.', 'woocommerce' ),
					'minItems'    => 1,
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'         => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'product_id' => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'quantity'   => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
						),
						'additionalProperties' => false,
					),
				),
				'shipping_lines' => array(
					'type'        => 'array',
					'description' => __( 'Send id to change a line. Without id, this adds a line, which needs method_title.', 'woocommerce' ),
					'minItems'    => 1,
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'           => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'method_id'    => array( 'type' => 'string' ),
							'method_title' => array( 'type' => 'string' ),
							'total'        => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
				),
				'remove_items'   => array(
					'type'        => 'array',
					'description' => __( 'IDs of the product and shipping lines to remove.', 'woocommerce' ),
					'minItems'    => 1,
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}
}
