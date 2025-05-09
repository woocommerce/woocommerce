<?php
/**
 * Order Item
 *
 * A class which represents an item within an order and handles CRUD.
 * Uses ArrayAccess to be BW compatible with WC_Orders::get_items().
 *
 * @package WooCommerce\Classes
 * @version 3.0.0
 * @since   3.0.0
 */

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Order item class.
 */
class WC_Order_Item extends WC_Data implements ArrayAccess {
	use CogsAwareTrait;

	/**
	 * Legacy cart item values.
	 *
	 * @deprecated 4.4.0 For legacy actions.
	 * @var array
	 */
	public $legacy_values;

	/**
	 * Legacy cart item keys.
	 *
	 * @deprecated 4.4.0 For legacy actions.
	 * @var string
	 */
	public $legacy_cart_item_key;

	/**
	 * Order Data array. This is the core order data exposed in APIs since 3.0.0.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $data = array(
		'order_id' => 0,
		'name'     => '',
	);

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'order-items';

	/**
	 * Meta type. This should match up with
	 * the types available at https://developer.wordpress.org/reference/functions/add_metadata/.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 *
	 * @var string
	 */
	protected $meta_type = 'order_item';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'order_item';

	/**
	 * Legacy package key.
	 *
	 * @deprecated 4.4.0 For legacy actions.
	 * @var string
	 */
	public $legacy_package_key;

	/**
	 * Constructor.
	 *
	 * @param int|object|array $item ID to load from the DB, or WC_Order_Item object.
	 */
	public function __construct( $item = 0 ) {
		if ( $this->has_cogs() && $this->cogs_is_enabled() ) {
			$this->data['cogs_value'] = null;
		}

		parent::__construct( $item );

		if ( $item instanceof WC_Order_Item ) {
			$this->set_id( $item->get_id() );
		} elseif ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		} else {
			$this->set_object_read( true );
		}

		if ( $this->get_id() && __CLASS__ === get_class( $this ) ) {
			wc_doing_it_wrong( __METHOD__, 'WC_Order_Item should not be instantiated directly.', '9.9.0' );
			return;
		}

		$type             = 'line_item' === $this->get_type() ? 'product' : $this->get_type();
		$this->data_store = WC_Data_Store::load( 'order-item-' . $type );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Merge changes with data and clear.
	 * Overrides WC_Data::apply_changes.
	 * array_replace_recursive does not work well for order items because it merges taxes instead
	 * of replacing them.
	 *
	 * @since 3.2.0
	 */
	public function apply_changes() {
		$this->data    = array_replace( $this->data, $this->changes );
		$this->changes = array();
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order ID this meta belongs to.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	/**
	 * Get order item name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get order item type. Overridden by child classes.
	 *
	 * @return string
	 */
	public function get_type() {
		return '';
	}

	/**
	 * Get quantity.
	 *
	 * @return int
	 */
	public function get_quantity() {
		return 1;
	}

	/**
	 * Get tax status.
	 *
	 * @return string
	 */
	public function get_tax_status() {
		return ProductTaxStatus::TAXABLE;
	}

	/**
	 * Get tax class.
	 *
	 * @return string
	 */
	public function get_tax_class() {
		return '';
	}

	/**
	 * Get parent order object.
	 *
	 * @return WC_Order
	 */
	public function get_order() {
		return wc_get_order( $this->get_order_id() );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order ID.
	 *
	 * @param int $value Order ID.
	 */
	public function set_order_id( $value ) {
		$this->set_prop( 'order_id', absint( $value ) );
	}

	/**
	 * Set order item name.
	 *
	 * @param string $value Item name.
	 */
	public function set_name( $value ) {
		$this->set_prop( 'name', wp_check_invalid_utf8( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Type checking.
	 *
	 * @param  string|array $type Type.
	 * @return boolean
	 */
	public function is_type( $type ) {
		return is_array( $type ) ? in_array( $this->get_type(), $type, true ) : $type === $this->get_type();
	}

	/**
	 * Calculate item taxes.
	 *
	 * @since  3.2.0
	 * @param  array $calculate_tax_for Location data to get taxes for. Required.
	 * @return bool  True if taxes were calculated.
	 */
	public function calculate_taxes( $calculate_tax_for = array() ) {
		if ( ! isset( $calculate_tax_for['country'], $calculate_tax_for['state'], $calculate_tax_for['postcode'], $calculate_tax_for['city'] ) ) {
			return false;
		}
		if ( '0' !== $this->get_tax_class() && ProductTaxStatus::TAXABLE === $this->get_tax_status() && wc_tax_enabled() ) {
			$calculate_tax_for['tax_class'] = $this->get_tax_class();
			$tax_rates                      = WC_Tax::find_rates( $calculate_tax_for );
			$taxes                          = WC_Tax::calc_tax( $this->get_total(), $tax_rates, false );

			if ( method_exists( $this, 'get_subtotal' ) ) {
				$subtotal_taxes = WC_Tax::calc_tax( $this->get_subtotal(), $tax_rates, false );
				$this->set_taxes(
					array(
						'total'    => $taxes,
						'subtotal' => $subtotal_taxes,
					)
				);
			} else {
				$this->set_taxes( array( 'total' => $taxes ) );
			}
		} else {
			$this->set_taxes( false );
		}

		do_action( 'woocommerce_order_item_after_calculate_taxes', $this, $calculate_tax_for );

		return true;
	}

	/*
	|--------------------------------------------------------------------------
	| Meta Data Handling
	|--------------------------------------------------------------------------
	*/

	/**
	 * Wrapper for get_formatted_meta_data that includes all metadata by default. See https://github.com/woocommerce/woocommerce/pull/30948
	 *
	 * @param string $hideprefix  Meta data prefix, (default: _).
	 * @param bool   $include_all Include all meta data, this stop skip items with values already in the product name.
	 * @return array
	 */
	public function get_all_formatted_meta_data( $hideprefix = '_', $include_all = true ) {
		return $this->get_formatted_meta_data( $hideprefix, $include_all );
	}

	/**
	 * Expands things like term slugs before return.
	 *
	 * @param string $hideprefix  Meta data prefix, (default: _).
	 * @param bool   $include_all Include all meta data, this stop skip items with values already in the product name.
	 * @return array
	 */
	public function get_formatted_meta_data( $hideprefix = '_', $include_all = false ) {
		$formatted_meta    = array();
		$meta_data         = $this->get_meta_data();
		$hideprefix_length = ! empty( $hideprefix ) ? strlen( $hideprefix ) : 0;
		$product           = is_callable( array( $this, 'get_product' ) ) ? $this->get_product() : false;
		$order_item_name   = $this->get_name();

		foreach ( $meta_data as $meta ) {
			if ( empty( $meta->id ) || '' === $meta->value || ! is_scalar( $meta->value ) || ( $hideprefix_length && substr( $meta->key, 0, $hideprefix_length ) === $hideprefix ) ) {
				continue;
			}

			$meta->key     = rawurldecode( (string) $meta->key );
			$meta->value   = rawurldecode( (string) $meta->value );
			$attribute_key = str_replace( 'attribute_', '', $meta->key );
			$display_key   = wc_attribute_label( $attribute_key, $product );
			$display_value = wp_kses_post( $meta->value );

			if ( taxonomy_exists( $attribute_key ) ) {
				$term = get_term_by( 'slug', $meta->value, $attribute_key );
				if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
					$display_value = $term->name;
				}
			}

			// Skip items with values already in the product details area of the product name.
			if ( ! $include_all && $product && $product->is_type( ProductType::VARIATION ) && wc_is_attribute_in_product_name( $display_value, $order_item_name ) ) {
				continue;
			}

			$formatted_meta[ $meta->id ] = (object) array(
				'key'           => $meta->key,
				'value'         => $meta->value,
				'display_key'   => apply_filters( 'woocommerce_order_item_display_meta_key', $display_key, $meta, $this ),
				'display_value' => wpautop( make_clickable( apply_filters( 'woocommerce_order_item_display_meta_value', $display_value, $meta, $this ) ) ),
			);
		}

		return apply_filters( 'woocommerce_order_item_get_formatted_meta_data', $formatted_meta, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Array Access Methods
	|--------------------------------------------------------------------------
	|
	| For backwards compatibility with legacy arrays.
	|
	*/

	/**
	 * OffsetSet for ArrayAccess.
	 *
	 * @param string $offset Offset.
	 * @param mixed  $value  Value.
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		if ( 'item_meta_array' === $offset ) {
			foreach ( $value as $meta_id => $meta ) {
				$this->update_meta_data( $meta->key, $meta->value, $meta_id );
			}
			return;
		}

		if ( array_key_exists( $offset, $this->data ) ) {
			$setter = "set_$offset";
			if ( is_callable( array( $this, $setter ) ) ) {
				$this->$setter( $value );
			}
			return;
		}

		$this->update_meta_data( $offset, $value );
	}

	/**
	 * OffsetUnset for ArrayAccess.
	 *
	 * @param string $offset Offset.
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		$this->maybe_read_meta_data();

		if ( 'item_meta_array' === $offset || 'item_meta' === $offset ) {
			$this->meta_data = array();
			return;
		}

		if ( array_key_exists( $offset, $this->data ) ) {
			unset( $this->data[ $offset ] );
		}

		if ( array_key_exists( $offset, $this->changes ) ) {
			unset( $this->changes[ $offset ] );
		}

		$this->delete_meta_data( $offset );
	}

	/**
	 * OffsetExists for ArrayAccess.
	 *
	 * @param string $offset Offset.
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		$this->maybe_read_meta_data();
		if ( 'item_meta_array' === $offset || 'item_meta' === $offset || array_key_exists( $offset, $this->data ) ) {
			return true;
		}
		return array_key_exists( $offset, wp_list_pluck( $this->meta_data, 'value', 'key' ) ) || array_key_exists( '_' . $offset, wp_list_pluck( $this->meta_data, 'value', 'key' ) );
	}

	/**
	 * OffsetGet for ArrayAccess.
	 *
	 * @param string $offset Offset.
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		$this->maybe_read_meta_data();

		if ( 'item_meta_array' === $offset ) {
			$return = array();

			foreach ( $this->meta_data as $meta ) {
				$return[ $meta->id ] = $meta;
			}

			return $return;
		}

		$meta_values = wp_list_pluck( $this->meta_data, 'value', 'key' );

		if ( 'item_meta' === $offset ) {
			return $meta_values;
		} elseif ( 'type' === $offset ) {
			return $this->get_type();
		} elseif ( array_key_exists( $offset, $this->data ) ) {
			$getter = "get_$offset";
			if ( is_callable( array( $this, $getter ) ) ) {
				return $this->$getter();
			}
		} elseif ( array_key_exists( '_' . $offset, $meta_values ) ) {
			// Item meta was expanded in previous versions, with prefixes removed. This maintains support.
			return $meta_values[ '_' . $offset ];
		} elseif ( array_key_exists( $offset, $meta_values ) ) {
			return $meta_values[ $offset ];
		}

		return null;
	}

	/**
	 * Indicates if the current order item has an associated Cost of Goods Sold value.
	 *
	 * Derived classes representing line items that have a COGS value
	 * should override this method to return "true" and also the 'calculate_cogs_value_core' method.
	 *
	 * @since 9.5.0
	 *
	 * @return bool True if this line item has an associated Cost of Goods Sold value.
	 */
	public function has_cogs(): bool {
		return false;
	}

	/**
	 * Calculate the Cost of Goods Sold value and set it as the actual value for this line item.
	 *
	 * @since 9.5.0
	 *
	 * @return bool True if the value has been calculated successfully (and set as the actual value), false otherwise (and the value hasn't changed).
	 * @throws Exception The class doesn't implement its own version of calculate_cogs_value_core. Derived classes are expected to override that method when has_cogs returns true.
	 */
	public function calculate_cogs_value(): bool {
		if ( ! $this->has_cogs() || ! $this->cogs_is_enabled( __METHOD__ ) ) {
			return false;
		}

		$value = $this->calculate_cogs_value_core();

		/**
		 * Filter to modify the Cost of Goods Sold value that gets calculated for a given order item.
		 *
		 * @since 9.5.0
		 *
		 * @param float|null $value The value originally calculated, null if it was not possible to calculate it.
		 * @param WC_Order_Item $line_item The order item for which the value is calculated.
		 */
		$value = apply_filters( 'woocommerce_calculated_order_item_cogs_value', $value, $this );

		if ( is_null( $value ) ) {
			return false;
		}

		$this->set_cogs_value( (float) $value );
		return true;
	}

	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn

	/**
	 * Core method to calculate the Cost of Goods Sold value for this line item:
	 * it doesn't check if COGS is enabled at class or system level, doesn't fire hooks, and doesn't set the value as the current one for the line item.
	 *
	 * @return float|null The calculated value, or null if the value can't be calculated for some reason.
	 * @throws Exception The class doesn't implement its own version of this method. Derived classes are expected to override this method when has_cogs returns true.
	 */
	protected function calculate_cogs_value_core(): ?float {
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		throw new Exception(
			sprintf(
				// translators: %1$s = class and method name.
				__( 'Method %1$s is not implemented. Classes overriding has_cogs must override this method too.', 'woocommerce' ),
				__METHOD__
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn

	/**
	 * Get the value of the Cost of Goods Sold for this order item.
	 *
	 * WARNING! If the Cost of Goods Sold feature is disabled this method will always return zero.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @return float The current value for this order item.
	 */
	public function get_cogs_value( $context = 'view' ): float {
		return (float) ( $this->has_cogs() && $this->cogs_is_enabled( __METHOD__ ) ? $this->get_prop( 'cogs_value', $context ) : 0 );
	}

	/**
	 * Set the value of the Cost of Goods Sold for this order item.
	 * Usually you'll want to use calculate_cogs_value instead.
	 *
	 * WARNING! If the Cost of Goods Sold feature is disabled this method will have no effect.
	 *
	 * @param float $value The value to set for this order item.
	 *
	 * @internal This method is intended for data store usage only, the value set here will be overridden by calculate_cogs_value.
	 */
	public function set_cogs_value( float $value ): void {
		if ( $this->has_cogs() && $this->cogs_is_enabled( __METHOD__ ) ) {
			$this->set_prop( 'cogs_value', $value );
		}
	}

	/**
	 * Returns the Cost of Goods Sold value in html format.
	 *
	 * @return string
	 */
	public function get_cogs_value_html(): string {
		if ( ! $this->cogs_is_enabled( __METHOD__ ) ) {
			return '';
		}

		if ( ! $this->has_cogs() ) {
			/**
			 * Filter to customize how a non-existing Cost of Goods Sold value for an order item (whose has_cogs method returns false) gets rendered to HTML.
			 *
			 * @param string $html The rendered HTML.
			 * @param WC_Order_Item $product The order item for which the "there's no cost" indication is rendered.
			 *
			 * @since 9.9.0
			 */
			return apply_filters( 'woocommerce_order_item_no_cogs_html', "<span class='na'>&ndash;</span>", $this );
		}

		$cogs_value      = $this->get_cogs_value();
		$cogs_value_html = wc_price( $cogs_value, array( 'currency' => $this->get_order()->get_currency() ) );

		/**
		 * Filter to customize how the Cost of Goods Sold value for an order item gets rendered to HTML.
		 *
		 * @param string $html The rendered HTML.
		 * @param float $value The cost value that is being rendered.
		 * @param WC_Order_Item $product The order item.
		 *
		 * @since 9.9.0
		 */
		return apply_filters( 'woocommerce_order_item_cogs_html', $cogs_value_html, $cogs_value, $this );
	}

	/**
	 * Get the "cost per unit" tooltip text for the "Cost" (of Goods Sold) column in the order details page.
	 *
	 * @return string "Cost per unit: (formatted cost with currency)" text.
	 */
	public function get_cogs_value_per_unit_tooltip_text(): string {
		if ( ! $this->cogs_is_enabled( __METHOD__ ) || ! $this->has_cogs() ) {
			return '';
		}

		$tooltip_text            = '';
		$quantity                = $this->get_quantity();
		$cogs_value              = $this->get_cogs_value();
		$cost_per_item           = 0;
		$formatted_cost_per_item = '';

		if ( $quantity > 0 && $cogs_value > 0 ) {
			$cost_per_item           = $cogs_value / $quantity;
			$formatted_cost_per_item = wc_price(
				$cost_per_item,
				array(
					'currency' => $this->get_order()->get_currency(),
					'in_span'  => false,
				)
			);
			/* translators: %s = formatted cost with currency symbol. */
			$tooltip_text = sprintf( __( 'Cost per unit: %s', 'woocommerce' ), $formatted_cost_per_item );
		}

		/**
		 * Filter to customize the text of the "Cost per unit" tooltip for the "Cost" (of Goods Sold) column in the order details page.
		 * If an empty string is returned then the tooltip won't be rendered.
		 *
		 * @param string $tooltip_text Original tooltip text, may be an empty string.
		 * @param float $cost_per_item The numerical value of the unit Cost of Goods Sold of the product.
		 * @param string $formatted_cost_per_item The unit Cost of Goods Sold of the product already formatted for display.
		 * @param WC_Order_Item $order_item The order item this filter is being fired for.
		 *
		 * @since 9.9.0
		 */
		return apply_filters( 'woocommerce_order_item_cogs_per_item_tooltip', $tooltip_text, $cost_per_item, $formatted_cost_per_item, $this );
	}
}
