<?php
/**
 * Order edit trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\Admin\Orders\ItemQuantityLimits;

defined( 'ABSPATH' ) || exit;

/**
 * Address and line rules of the abilities that write an order, as the order screen applies them. Every
 * change stays in memory until the ability saves. A class that uses this trait also uses OrderAbilityTrait.
 */
trait OrderEditTrait {

	use AddressInputTrait;
	use PaymentGatewayAbilityTrait;

	/**
	 * Product lines that change() added or gave a new quantity, for a stock change after the save.
	 *
	 * @var array<int, \WC_Order_Item_Product>
	 */
	private array $changed_product_lines = array();

	/**
	 * Whether recalculate_saved_order() recalculates with the methods that save, and which ones.
	 * Only with the feature off, when no validator needs to see the new totals.
	 *
	 * @var string|null `coupons`, `totals` or null.
	 */
	private ?string $recalculate_after_save = null;

	/**
	 * Product lines for add_queued_products(), with the feature off. WC_Order::add_product() runs the rules of
	 * an order class that has not adopted stage_product_item(), as order-update-status does with update_status().
	 *
	 * @var array<int, array{0: \WC_Product, 1: int}>
	 */
	private array $products_to_add = array();

	/**
	 * Product lines that change() added or changed, as secondary objects of the `order_item` resource with
	 * their `extensions` input. The save of the order saves them.
	 *
	 * @var array<int, array{0: \WC_Order_Item_Product, 1: array, 2: string}>
	 */
	private array $line_objects = array();

	/**
	 * Whether change() added a product line, for the totals in stage_changed_line_totals(). Null when no line changed.
	 *
	 * @var bool|null
	 */
	private ?bool $product_added = null;

	/**
	 * Forget the line changes of an earlier call, since the ability object is reused.
	 */
	private function reset_line_changes(): void {
		$this->changed_product_lines  = array();
		$this->recalculate_after_save = null;
		$this->products_to_add        = array();
		$this->line_objects           = array();
		$this->product_added          = null;
	}

	/**
	 * Recalculate the totals when change() changed a line. It runs in finish_change(), so the totals include
	 * a price that the `set_callback` of an `order_item` field gave a line.
	 *
	 * @param \WC_Order $order Order.
	 * @throws \WC_Data_Exception When the order class has not adopted a stage method.
	 */
	private function stage_changed_line_totals( \WC_Order $order ): void {
		if ( null !== $this->product_added ) {
			$this->stage_line_totals( $order, $this->product_added );
		}
	}

	/**
	 * Whether an `order_item` field applies to a product line, from the `object_types` of the field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return bool
	 */
	public static function field_applies_to_product_lines( array $args ): bool {
		return empty( $args['object_types'] ) || in_array( 'line_item', $args['object_types'], true );
	}

	/**
	 * Add the `extensions` input of the `order_item` resource to the properties of a `line_items` entry,
	 * when the feature is on.
	 *
	 * @param array<string, array> $properties Properties of one product line.
	 * @return array<string, array>
	 */
	private static function add_line_item_extensions_schema( array $properties ): array {
		if ( ExtensibleAbilities::is_enabled() ) {
			$properties['extensions'] = ExtensibleAbilities::get_input_schema( 'order_item', array( self::class, 'field_applies_to_product_lines' ) );
		}
		return $properties;
	}

	/**
	 * Say what is wrong with each bad extension field of the input and of its `line_items` entries. The
	 * message of an entry starts with the entry, as `line_items[0]: `.
	 *
	 * @param array $input Ability input that the schema refused.
	 * @return string[]
	 */
	protected function get_field_refusals( array $input ): array {
		$refusals = parent::get_field_refusals( $input );
		foreach ( ExtensibleAbilities::is_enabled() && is_array( $input['line_items'] ?? null ) ? $input['line_items'] : array() as $index => $line ) {
			foreach ( self::describe_bad_fields( $line['extensions'] ?? null, 'order_item', array( self::class, 'field_applies_to_product_lines' ) ) as $message ) {
				$refusals[] = 'line_items[' . $index . ']: ' . $message;
			}
		}
		return $refusals;
	}

	/**
	 * Recalculate the totals in memory after the lines changed. Coupons apply again after a product is
	 * added, because they were applied before the new line existed. With the feature off, the totals wait
	 * for recalculate_saved_order(), which uses the methods that save.
	 *
	 * @param \WC_Order $order         Order.
	 * @param bool      $product_added Whether a product line was added.
	 * @throws \WC_Data_Exception When the order class has not adopted a stage method.
	 */
	private function stage_line_totals( \WC_Order $order, bool $product_added ): void {
		$recalculate_coupons = $product_added && ! empty( $order->get_items( 'coupon' ) );

		if ( ! ExtensibleAbilities::is_enabled() ) {
			$this->recalculate_after_save = $recalculate_coupons ? 'coupons' : 'totals';
			return;
		}

		if ( $recalculate_coupons ) {
			if ( $order->get_prices_include_tax() ) {
				$order->stage_taxes();
			}

			$order->stage_recalculated_coupons();

			return;
		}

		$order->stage_totals();
	}

	/**
	 * Add the product lines that add_product_line() left for the save with the feature off.
	 * WC_Order::add_product() saves each line right away.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<int, \WC_Order_Item_Product> The added lines, to delete when the order save fails.
	 */
	private function add_queued_products( \WC_Order $order ): array {
		$added = array();
		foreach ( $this->products_to_add as list( $product, $quantity ) ) {
			$item = $order->get_item( $order->add_product( $product, $quantity, array( 'order' => $order ) ), false );
			if ( $item instanceof \WC_Order_Item_Product ) {
				$added[] = $item;
			}
		}

		return $added;
	}

	/**
	 * Recalculate the saved order with the methods that save, when stage_line_totals() left it for later.
	 * Each step is checked on its own, because the next save clears the error of a failed one.
	 *
	 * @param \WC_Order $order Saved order.
	 * @return null|\WP_Error
	 */
	private function recalculate_saved_order( \WC_Order $order ): ?\WP_Error {
		$steps = array();
		if ( 'coupons' === $this->recalculate_after_save ) {
			if ( $order->get_prices_include_tax() ) {
				$steps[] = array( $order, 'calculate_taxes' );
			}
			$steps[] = array( $order, 'recalculate_coupons' );
		} elseif ( 'totals' === $this->recalculate_after_save ) {
			$steps[] = array( $order, 'calculate_totals' );
		}

		foreach ( $steps as $step ) {
			$step();
			$error = $order->get_last_save_error();
			if ( null !== $error ) {
				wc_get_logger()->error(
					sprintf( 'Ability "%s" failed to save the recalculated order: %s', $this->get_name(), $error->getMessage() ),
					array( 'source' => 'woocommerce-abilities' )
				);
				return new \WP_Error(
					'woocommerce_ability_save_failed',
					__( 'The change could not be saved.', 'woocommerce' ),
					array( 'status' => 500 )
				);
			}
		}

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
		// wc_format_decimal() drops the exponent of a number such as 1e3, which changes its value.
		if ( isset( $line['total'] ) && ( ! is_numeric( $line['total'] ) || false !== stripos( (string) $line['total'], 'e' ) || (float) $line['total'] < 0 ) ) {
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
	 * Set the payment method and its title in memory. Only an enabled gateway is accepted. Nothing is charged
	 * or captured: the gateway only names how the order was paid. The gateway the order already has changes
	 * nothing, so a title that a gateway set for this order stays.
	 *
	 * @param \WC_Order $order      Order.
	 * @param string    $gateway_id Gateway ID sent by the client.
	 * @return null|\WP_Error
	 */
	private static function set_payment_method( \WC_Order $order, string $gateway_id ): ?\WP_Error {
		if ( '' !== $gateway_id && $order->get_payment_method() === $gateway_id ) {
			return null;
		}

		$enabled = array_filter(
			WC()->payment_gateways()->payment_gateways(),
			static fn( $gateway ) => $gateway instanceof \WC_Payment_Gateway && 'yes' === $gateway->enabled
		);

		if ( ! isset( $enabled[ $gateway_id ] ) ) {
			if ( empty( $enabled ) ) {
				/* translators: %s: payment gateway ID sent by the client. */
				$message = sprintf( __( '"%s" is not an enabled payment gateway, and the store has no enabled payment gateways. Enable one with the woocommerce/payment-gateway-update ability, or leave out payment_method.', 'woocommerce' ), $gateway_id );
			} else {
				/* translators: 1: payment gateway ID sent by the client, 2: comma-separated list of gateway IDs. */
				$message = sprintf( __( '"%1$s" is not an enabled payment gateway. Send one of: %2$s.', 'woocommerce' ), $gateway_id, implode( ', ', array_map( 'strval', array_keys( $enabled ) ) ) );
			}

			return new \WP_Error( 'woocommerce_ability_order_payment_method_invalid', $message, array( 'status' => 400 ) );
		}

		$order->set_payment_method( $gateway_id );
		$order->set_payment_method_title( self::get_payment_gateway_title( $enabled[ $gateway_id ] ) );

		return null;
	}

	/**
	 * Input schema of payment_method, shared by order-create and order-update.
	 *
	 * @return array<string, string>
	 */
	private static function get_payment_method_input_schema(): array {
		return array(
			'type'        => 'string',
			'description' => __( 'ID of an enabled payment gateway, such as bacs or cheque, to record how the order was paid. Sets the payment method title from the gateway. Nothing is charged. Use the woocommerce/payment-gateways-query ability to list the gateways.', 'woocommerce' ),
		);
	}

	/**
	 * Add a product line in memory. With the feature off, the line waits for add_queued_products(), so an order
	 * class that has not adopted stage_product_item() keeps working.
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

		$item                          = $order->stage_product_item( $product, $quantity, array( 'order' => $order ) );
		$this->changed_product_lines[] = $item;
		$this->line_objects[]          = array( $item, $line['extensions'] ?? array(), 'order_item' );

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
			sprintf( __( 'Item %d is not a line of this order that this ability can change. Use the woocommerce/orders-query ability with include_line_items and include_lines to read the IDs of the product and shipping lines.', 'woocommerce' ), $item_id ),
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
}
