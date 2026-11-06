<?php
/**
 * Order update ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
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

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/order-update';

	/**
	 * Address fields that the shipping address has. The billing address also has `email`.
	 */
	private const ADDRESS_FIELDS = array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone' );

	/**
	 * Product lines that change() removed, for the stock change in save().
	 *
	 * @var array<int, \WC_Order_Item_Product>
	 */
	private array $removed_product_lines = array();

	/**
	 * Product lines that change() added or gave a new quantity, for the stock change in save().
	 *
	 * @var array<int, \WC_Order_Item_Product>
	 */
	private array $changed_product_lines = array();

	/**
	 * Whether save() recalculates the order with the methods that save, and which ones.
	 * Only with the feature off, when no validator needs to see the new totals.
	 *
	 * @var string|null `coupons`, `totals` or null.
	 */
	private ?string $recalculate_after_save = null;

	/**
	 * Product lines to add with WC_Order::add_product() in save(), with the feature off. The saving method
	 * runs the rules of an order class that has not adopted stage_product_item(), as order-update-status does
	 * with update_status().
	 *
	 * @var array<int, array{0: \WC_Product, 1: int}>
	 */
	private array $products_to_add = array();

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
		$this->removed_product_lines  = array();
		$this->changed_product_lines  = array();
		$this->recalculate_after_save = null;
		$this->products_to_add        = array();

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

		$added = array();
		foreach ( $this->products_to_add as list( $product, $quantity ) ) {
			$item = $subject->get_item( $subject->add_product( $product, $quantity, array( 'order' => $subject ) ), false );
			if ( $item instanceof \WC_Order_Item_Product ) {
				$added[] = $item;
			}
		}

		$saved = parent::save( $subject );

		if ( is_wp_error( $saved ) ) {
			foreach ( $added as $item ) {
				$item->delete();
			}
			return $saved;
		}

		$this->changed_product_lines = array_merge( $this->changed_product_lines, $added );

		if ( 'coupons' === $this->recalculate_after_save ) {
			if ( $subject->get_prices_include_tax() ) {
				$subject->calculate_taxes();
			}
			$subject->recalculate_coupons();
		} elseif ( 'totals' === $this->recalculate_after_save ) {
			$subject->calculate_totals();
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
		$order = self::format_order_for_response( $subject, true );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		return array( 'order' => $order );
	}

	/**
	 * Check the sent fields of an address against the order, then set the fields
	 * that pass. A bad country keeps the saved country, state and postcode, because
	 * the state and postcode checks depend on the country.
	 *
	 * @param \WC_Order $order   Order.
	 * @param string    $type    Address type: billing or shipping.
	 * @param array     $address Address fields to change.
	 * @return null|\WP_Error
	 */
	private static function set_address( \WC_Order $order, string $type, array $address ): ?\WP_Error {
		$address = wc_clean( $address );
		$refused = new \WP_Error();
		if ( ! empty( $address['email'] ) && ! is_email( $address['email'] ) ) {
			$refused->merge_from(
				self::address_error(
					$type,
					'email',
					__( 'The billing email is not a valid email address. Send a valid address.', 'woocommerce' )
				)
			);
			unset( $address['email'] );
		}

		$country  = $address['country'] ?? $order->{"get_{$type}_country"}();
		$state    = $address['state'] ?? $order->{"get_{$type}_state"}();
		$postcode = $address['postcode'] ?? $order->{"get_{$type}_postcode"}();

		if ( '' !== $country && ! array_key_exists( $country, WC()->countries->get_countries() ) ) {
			$refused->merge_from(
				self::address_error(
					$type,
					'country',
					/* translators: %s: country sent by the client. */
					sprintf( __( '"%s" is not a valid country. Send an ISO 3166-1 alpha-2 country code, such as PT.', 'woocommerce' ), $country )
				)
			);
			unset( $address['country'], $address['state'], $address['postcode'] );
		} else {
			$states = WC()->countries->get_states( $country );

			if ( '' !== $state && ! empty( $states ) && ! array_key_exists( $state, $states ) ) {
				$refused->merge_from(
					self::address_error(
						$type,
						'state',
						/* translators: 1: state sent by the client or kept from the order, 2: country code, 3: comma-separated state codes. */
						sprintf( __( '"%1$s" is not a state of %2$s. Send the state with one of these codes: %3$s.', 'woocommerce' ), $state, $country, implode( ', ', array_keys( $states ) ) )
					)
				);
				unset( $address['state'] );
			}

			if ( '' !== $postcode && '' !== $country ) {
				if ( ! \WC_Validation::is_postcode( $postcode, $country ) ) {
					$refused->merge_from(
						self::address_error(
							$type,
							'postcode',
							/* translators: 1: postcode sent by the client or kept from the order, 2: country code. */
							sprintf( __( '"%1$s" is not a valid postcode for %2$s. Send a postcode in the format of that country.', 'woocommerce' ), $postcode, $country )
						)
					);
					unset( $address['postcode'] );
				} elseif ( isset( $address['postcode'] ) ) {
					$address['postcode'] = wc_format_postcode( $postcode, $country );
				}
			}
		}

		foreach ( $address as $key => $value ) {
			$order->{"set_{$type}_{$key}"}( $value );
		}

		return $refused->has_errors() ? $refused : null;
	}

	/**
	 * Build the error for an address field.
	 *
	 * @param string $type    Address type: billing or shipping.
	 * @param string $field   Address field.
	 * @param string $message What is wrong and what to send.
	 * @return \WP_Error
	 */
	private static function address_error( string $type, string $field, string $message ): \WP_Error {
		return new \WP_Error( "woocommerce_ability_order_{$type}_{$field}_invalid", $message, array( 'status' => 400 ) );
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

		$recalculate_coupons = $product_added && ! empty( $order->get_items( 'coupon' ) );

		if ( ! ExtensibleAbilities::is_enabled() ) {
			$this->recalculate_after_save = $recalculate_coupons ? 'coupons' : 'totals';
			return null;
		}

		if ( $recalculate_coupons ) {
			if ( $order->get_prices_include_tax() ) {
				$order->stage_taxes();
			}

			$order->stage_recalculated_coupons();

			return null;
		}

		$order->stage_totals();

		return null;
	}

	/**
	 * Change a shipping line in memory, or add a new one when the line has no ID.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $line  Shipping line input.
	 * @return null|\WP_Error
	 */
	private static function set_shipping_line( \WC_Order $order, array $line ): ?\WP_Error {
		if ( isset( $line['total'] ) && ( ! is_numeric( $line['total'] ) || (float) $line['total'] < 0 ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_shipping_line_total_invalid',
				/* translators: %s: shipping total sent by the client. */
				sprintf( __( '"%s" is not a valid shipping total. Send a decimal number that is not negative, such as 9.99.', 'woocommerce' ), $line['total'] ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $line['id'] ) ) {
			if ( empty( $line['method_title'] ) ) {
				return new \WP_Error(
					'woocommerce_ability_order_shipping_line_method_title_required',
					__( 'A new shipping line needs a method_title. Send method_title, or send the id of the shipping line to change.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$item = new \WC_Order_Item_Shipping();
		} else {
			$item = $order->get_item( $line['id'], false );

			if ( ! $item instanceof \WC_Order_Item_Shipping ) {
				return self::line_not_found( $line['id'] );
			}
		}

		if ( isset( $line['method_id'] ) ) {
			$item->set_method_id( sanitize_text_field( $line['method_id'] ) );
		}

		if ( isset( $line['method_title'] ) ) {
			$item->set_method_title( sanitize_text_field( $line['method_title'] ) );
		}

		if ( isset( $line['total'] ) ) {
			$item->set_total( wc_format_decimal( $line['total'] ) );
		}

		if ( empty( $line['id'] ) ) {
			$order->add_item( $item );
		}

		return null;
	}

	/**
	 * Add a product line in memory. save() reduces its stock. With the feature off, save() adds the line with
	 * WC_Order::add_product(), so an order class that has not adopted stage_product_item() keeps working.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $line  Product line input.
	 * @return null|\WP_Error
	 * @throws \WC_Data_Exception With the feature on, when the order class has not adopted stage_product_item().
	 */
	private function add_product_line( \WC_Order $order, array $line ): ?\WP_Error {
		$product  = wc_get_product( $line['product_id'] );
		$quantity = $line['quantity'] ?? 1;

		if ( ! $product instanceof \WC_Product ) {
			return new \WP_Error(
				'woocommerce_ability_order_product_not_found',
				/* translators: %d: product ID. */
				sprintf( __( 'Product %d does not exist. Use the woocommerce/products-query ability to find the product ID.', 'woocommerce' ), $line['product_id'] ),
				array( 'status' => 400 )
			);
		}

		if ( $product->is_type( ProductType::VARIABLE ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_product_variable',
				/* translators: %d: product ID. */
				sprintf( __( 'Product %d is a variable product and cannot be added. Send the ID of one of its variations as product_id.', 'woocommerce' ), $line['product_id'] ),
				array( 'status' => 400 )
			);
		}

		try {
			wc_get_container()->get( ItemQuantityLimits::class )->validate_new_item_quantity( $quantity, $product );
		} catch ( \Exception $exception ) {
			return self::quantity_error( $exception );
		}

		if ( ! ExtensibleAbilities::is_enabled() ) {
			$this->products_to_add[] = array( $product, $quantity );
			return null;
		}

		$this->changed_product_lines[] = $order->stage_product_item( $product, $quantity, array( 'order' => $order ) );

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
	 * Build the error for a line that the order does not have.
	 *
	 * @param int $item_id Order item ID sent by the client.
	 * @return \WP_Error
	 */
	private static function line_not_found( int $item_id ): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_order_line_not_found',
			/* translators: %d: order item ID. */
			sprintf( __( 'Item %d is not a line of this order that this ability can change. Use the woocommerce/orders-query ability with include_line_items to read the IDs of the product and shipping lines.', 'woocommerce' ), $item_id ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Build the error for a quantity below the minimum of a product.
	 *
	 * @param \Exception $exception The refusal of ItemQuantityLimits, which names the product and the minimum.
	 * @return \WP_Error
	 */
	private static function quantity_error( \Exception $exception ): \WP_Error {
		return new \WP_Error( 'woocommerce_ability_order_line_item_quantity_invalid', $exception->getMessage(), array( 'status' => 400 ) );
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
				'billing'        => self::get_address_input_schema( array_merge( self::ADDRESS_FIELDS, array( 'email' ) ) ),
				'shipping'       => self::get_address_input_schema( self::ADDRESS_FIELDS ),
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

	/**
	 * Get the input schema of an address.
	 *
	 * @param array<int, string> $fields Address fields.
	 * @return array
	 */
	private static function get_address_input_schema( array $fields ): array {
		return array(
			'type'                 => 'object',
			'description'          => __( 'Countries use ISO 3166-1 alpha-2 codes, and states use WooCommerce state codes.', 'woocommerce' ),
			'properties'           => array_fill_keys( $fields, array( 'type' => 'string' ) ),
			'additionalProperties' => false,
			'minProperties'        => 1,
		);
	}
}
