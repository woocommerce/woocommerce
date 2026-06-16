<?php
/**
 * MultiCurrencyProductAddOnsCompatibilityController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceCalculator;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProductAddOnsCompatibilityProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency Product Add-ons compatibility hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyProductAddOnsCompatibilityController implements RegisterHooksInterface {

	private const ADDONS_CONVERTED_META_KEY = '_wcpay_multi_currency_addons_converted';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Price projection service.
	 *
	 * @var MultiCurrencyPriceProjectionService|null
	 */
	private ?MultiCurrencyPriceProjectionService $price_projection_service = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter $arbiter Runtime owner arbiter.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter ): void {
		$this->arbiter = $arbiter;
	}

	/**
	 * Set the price projection service.
	 *
	 * @internal Used by tests and future explicit bootstrap definitions.
	 *
	 * @param MultiCurrencyPriceProjectionService $price_projection_service Price projection service.
	 */
	public function set_price_projection_service( MultiCurrencyPriceProjectionService $price_projection_service ): void {
		$this->price_projection_service = $price_projection_service;
	}

	/**
	 * Register Product Add-ons compatibility hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		if ( $this->is_product_addons_runtime_available() || $this->have_plugins_loaded() ) {
			$this->register_product_addons_hooks();
			return;
		}

		$this->add_action_once( 'plugins_loaded', array( $this, 'register_product_addons_hooks' ), 20 );
	}

	/**
	 * Register Product Add-ons compatibility hooks after supported runtimes have loaded.
	 *
	 * @internal
	 */
	public function register_product_addons_hooks(): void {
		$is_admin = $this->is_admin_request();
		$is_ajax  = $this->is_ajax_request();
		$is_cron  = $this->is_cron_request();

		if (
			! $this->arbiter->should_core_register()
			|| ! MultiCurrencyProductAddOnsCompatibilityProjectionService::should_register(
				$this->is_product_addons_runtime_available(),
				$is_admin,
				$is_ajax,
				$is_cron
			)
		) {
			return;
		}

		if ( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_register_frontend_filters( $is_admin, $is_cron ) ) {
			$this->register_filter_manifest( MultiCurrencyProductAddOnsCompatibilityProjectionService::get_frontend_filter_manifest() );
		}

		if ( $is_ajax ) {
			$this->register_filter_manifest( MultiCurrencyProductAddOnsCompatibilityProjectionService::get_ajax_filter_manifest() );
		}
	}

	/**
	 * Tell whether native product-price conversion should run for Product Add-ons products.
	 *
	 * @param bool  $should_convert Existing product conversion decision.
	 * @param mixed $product        Product.
	 * @return bool
	 */
	public function should_convert_product_price( bool $should_convert, $product ): bool {
		$addons_were_converted = is_object( $product ) && is_callable( array( $product, 'get_meta' ) )
			? 1 === (int) $product->get_meta( self::ADDONS_CONVERTED_META_KEY )
			: false;

		return MultiCurrencyProductAddOnsCompatibilityProjectionService::should_convert_product_price( $should_convert, $addons_were_converted );
	}

	/**
	 * Convert raw add-on prices for the selected currency.
	 *
	 * @param mixed $price Raw add-on price.
	 * @param mixed $type  Add-on type metadata.
	 * @return mixed
	 */
	public function get_addons_price( $price, $type ) {
		$price_type = is_array( $type ) ? (string) ( $type['price_type'] ?? '' ) : '';

		if ( ! MultiCurrencyProductAddOnsCompatibilityProjectionService::should_convert_addon_price( $price_type ) ) {
			return $price;
		}

		return $this->get_price_projection_service()->get_price( $price, 'product' );
	}

	/**
	 * Update Product Add-ons script params with selected-currency formatting.
	 *
	 * @param array<mixed> $params Product Add-ons params.
	 * @return array<mixed>
	 */
	public function product_addons_params( array $params ): array {
		$params['currency_format_num_decimals'] = wc_get_price_decimals();
		$params['currency_format_decimal_sep']  = wc_get_price_decimal_separator();
		$params['currency_format_thousand_sep'] = wc_get_price_thousand_separator();

		return $params;
	}

	/**
	 * Rebuild cart item add-on display data with converted price labels.
	 *
	 * @param array<mixed> $addon_data Existing add-on data.
	 * @param array<mixed> $addon      Add-on data.
	 * @param array<mixed> $cart_item  Cart item data.
	 * @return array<mixed>
	 */
	public function get_item_data( $addon_data, $addon, $cart_item ): array {
		$price              = $cart_item['addons_price_before_calc'] ?? ( $addon['price'] ?? 0 );
		$value              = (string) ( $addon['value'] ?? '' );
		$display            = (string) ( $addon['display'] ?? '' );
		$addon_price        = $addon['price'] ?? 0;
		$addon_price_type   = (string) ( $addon['price_type'] ?? '' );
		$addon_field_type   = (string) ( $addon['field_type'] ?? '' );
		$add_price_to_value = $this->should_add_cart_price_to_value( $cart_item );

		if ( 0.0 === (float) $addon_price ) {
			$value .= '';
		} elseif ( 'percentage_based' === $addon_price_type && 0.0 === (float) $price ) {
			$value .= '';
		} elseif ( 'custom_price' === $addon_field_type && $addon_price ) {
			$formatted_price = $this->format_addon_price( $addon_price, $cart_item['data'] ?? null );
			/* translators: %1$s custom addon price in cart */
			$value  .= sprintf( _x( ' (%1$s)', 'custom price addon price in cart', 'woocommerce' ), $formatted_price );
			$display = $value;
		} elseif ( 'flat_fee' === $addon_price_type && $addon_price ) {
			$formatted_price = $this->format_addon_price( $this->get_addon_display_price( $addon ), $cart_item['data'] ?? null );
			/* translators: %1$s flat fee addon price in cart */
			$value .= sprintf( _x( ' (+ %1$s)', 'flat fee addon price in cart', 'woocommerce' ), $formatted_price );
		} elseif ( 'quantity_based' === $addon_price_type && $addon_price && $add_price_to_value ) {
			$formatted_price = $this->format_addon_price( $this->get_addon_display_price( $addon ), $cart_item['data'] ?? null );
			/* translators: %1$s addon price in cart */
			$value .= sprintf( _x( ' (%1$s)', 'quantity based addon price in cart', 'woocommerce' ), $formatted_price );
		} elseif ( 'percentage_based' === $addon_price_type && $addon_price && $add_price_to_value ) {
			$product = $this->get_product_by_id( (int) ( $cart_item['product_id'] ?? 0 ) );
			if ( is_object( $product ) && is_callable( array( $product, 'set_price' ) ) ) {
				$converted_price = $this->get_price_projection_service()->get_price( $price, 'product' );
				$product->set_price( $converted_price * ( (float) $addon_price / 100 ) );

				if ( is_callable( array( $product, 'update_meta_data' ) ) ) {
					$product->update_meta_data( self::ADDONS_CONVERTED_META_KEY, 1 );
				}

				/* translators: %1$s addon price in cart */
				$value .= sprintf( _x( ' (%1$s)', 'percentage based addon price in cart', 'woocommerce' ), $this->get_cart_product_price( $product ) );
			}
		}

		return array(
			'name'    => $addon['name'] ?? ( $addon_data['name'] ?? '' ),
			'value'   => $value,
			'display' => $display,
		);
	}

	/**
	 * Update Product Add-ons calculated product prices for the selected currency.
	 *
	 * @param array<mixed> $updated_prices Prices updated by Product Add-ons.
	 * @param array<mixed> $cart_item      Cart item data.
	 * @param array<mixed> $prices         Original prices.
	 * @param mixed        $product        Product.
	 * @return array<string,float>
	 */
	public function update_product_price( $updated_prices, $cart_item, $prices, $product = null ): array {
		unset( $updated_prices, $product );

		$price                       = $this->get_price_projection_service()->get_price( $prices['price'] ?? 0, 'product' );
		$regular_price               = $this->get_price_projection_service()->get_price( $prices['regular_price'] ?? 0, 'product' );
		$sale_price                  = $this->get_price_projection_service()->get_price( $prices['sale_price'] ?? 0, 'product' );
		$flat_fees                   = 0.0;
		$quantity                    = (float) ( $cart_item['quantity'] ?? 0 );
		$price_before_addons         = $price;
		$regular_price_before_addons = $regular_price;
		$sale_price_before_addons    = $sale_price;

		if ( empty( $price ) ) {
			$credit_amount = $this->get_smart_coupons_credit_amount( $cart_item );
			if ( null !== $credit_amount ) {
				$price         = $credit_amount;
				$regular_price = $credit_amount;
				$sale_price    = $credit_amount;
			}
		}

		foreach ( (array) ( $cart_item['addons'] ?? array() ) as $addon ) {
			if ( ! is_array( $addon ) ) {
				continue;
			}

			$addon_price = $this->get_addon_calculation_price( $addon );
			$price_type  = (string) ( $addon['price_type'] ?? '' );

			switch ( $price_type ) {
				case 'percentage_based':
					$price         += (float) ( $price_before_addons * ( $addon_price / 100 ) );
					$regular_price += (float) ( $regular_price_before_addons * ( $addon_price / 100 ) );
					$sale_price    += (float) ( $sale_price_before_addons * ( $addon_price / 100 ) );
					break;
				case 'flat_fee':
					$flat_fee       = $quantity > 0 ? (float) ( $addon_price / $quantity ) : 0.0;
					$price         += $flat_fee;
					$regular_price += $flat_fee;
					$sale_price    += $flat_fee;
					$flat_fees     += $flat_fee;
					break;
				default:
					$price         += (float) $addon_price;
					$regular_price += (float) $addon_price;
					$sale_price    += (float) $addon_price;
					break;
			}
		}

		if ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) && is_callable( array( $cart_item['data'], 'update_meta_data' ) ) ) {
			$cart_item['data']->update_meta_data( self::ADDONS_CONVERTED_META_KEY, 1 );
		}

		return array(
			'price'                => $price,
			'regular_price'        => $regular_price,
			'sale_price'           => $sale_price,
			'addons_flat_fees_sum' => $flat_fees,
		);
	}

	/**
	 * Rebuild order line item add-on meta with converted display prices.
	 *
	 * @param array<mixed> $meta_data Existing meta data.
	 * @param array<mixed> $addon     Add-on data.
	 * @param mixed        $item      Order item.
	 * @param array<mixed> $values    Order item values.
	 * @return array<mixed>
	 */
	public function order_line_item_meta( array $meta_data, array $addon, $item, array $values ): array {
		$add_price_to_value = $this->should_add_order_price_to_value( $item );
		$value              = isset( $addon['timestamp'] ) ? $addon['timestamp'] : ( $addon['value'] ?? '' );
		$addon_price        = $addon['price'] ?? 0;
		$addon_price_type   = (string) ( $addon['price_type'] ?? '' );
		$addon_field_type   = (string) ( $addon['field_type'] ?? '' );

		if ( $addon_price && $add_price_to_value ) {
			$product          = is_object( $item ) && is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : null;
			$calculated_price = $this->get_order_line_item_addon_price( $addon, $product );
			$formatted_price  = html_entity_decode(
				wp_strip_all_tags( $this->format_addon_price( $calculated_price, $values['data'] ?? null ) ),
				ENT_QUOTES,
				get_bloginfo( 'charset' )
			);

			if ( 'flat_fee' === $addon_price_type ) {
				/* translators: %1$s flat fee addon price in order */
				$value .= sprintf( _x( ' (+ %1$s)', 'flat fee addon price in order', 'woocommerce' ), $formatted_price );
			} elseif ( 'quantity_based' === $addon_price_type || 'percentage_based' === $addon_price_type ) {
				/* translators: %1$s addon price in order */
				$value .= sprintf( _x( ' (%1$s)', 'addon price in order', 'woocommerce' ), $formatted_price );
			} elseif ( 'custom_price' === $addon_field_type ) {
				/* translators: %1$s custom addon price in order */
				$value = sprintf( _x( ' (%1$s)', 'custom addon price in order', 'woocommerce' ), $formatted_price );
			}

			$meta_data['raw_price'] = $this->get_price_projection_service()->get_price( $addon_price, 'product' );
		}

		$meta_data['value'] = $value;

		return $meta_data;
	}

	/**
	 * Convert Product Add-ons Ajax calculation prices by unit price.
	 *
	 * @param float       $price    Price.
	 * @param int         $quantity Quantity.
	 * @param \WC_Product $product  Product.
	 * @return float
	 */
	public function get_product_calculation_price( float $price, int $quantity, \WC_Product $product ): float {
		unset( $product );

		if ( $quantity <= 0 ) {
			return $price;
		}

		return $this->get_price_projection_service()->get_price( $price / $quantity, 'product' ) * $quantity;
	}

	/**
	 * Check if Product Add-ons runtime is available.
	 *
	 * @return bool
	 */
	protected function is_product_addons_runtime_available(): bool {
		return class_exists( 'WC_Product_Addons' );
	}

	/**
	 * Check if plugins have loaded.
	 *
	 * @return bool
	 */
	protected function have_plugins_loaded(): bool {
		return did_action( 'plugins_loaded' ) > 0;
	}

	/**
	 * Check if this is an admin request.
	 *
	 * @return bool
	 */
	protected function is_admin_request(): bool {
		return is_admin();
	}

	/**
	 * Check if this is an Ajax request.
	 *
	 * @return bool
	 */
	protected function is_ajax_request(): bool {
		return wp_doing_ajax();
	}

	/**
	 * Check if this is a cron request.
	 *
	 * @return bool
	 */
	protected function is_cron_request(): bool {
		return wp_doing_cron();
	}

	/**
	 * Get a product by ID.
	 *
	 * @param int $product_id Product ID.
	 * @return object|null
	 */
	protected function get_product_by_id( int $product_id ): ?object {
		$product = wc_get_product( $product_id );

		return $product ? $product : null;
	}

	/**
	 * Format an add-on display price.
	 *
	 * @param mixed $price   Price.
	 * @param mixed $product Product.
	 * @return string
	 */
	protected function format_addon_price( $price, $product ): string {
		$display_price = $price;
		$helper        = array( 'WC_Product_Addons_Helper', 'get_product_addon_price_for_display' );

		if ( class_exists( 'WC_Product_Addons_Helper' ) && is_callable( $helper ) ) {
			$display_price = call_user_func( $helper, $price, $product );
		}

		return wc_price( $display_price );
	}

	/**
	 * Get a formatted cart product price.
	 *
	 * @param mixed $product Product.
	 * @return string
	 */
	protected function get_cart_product_price( $product ): string {
		if ( WC()->cart && is_callable( array( WC()->cart, 'get_product_price' ) ) ) {
			return WC()->cart->get_product_price( $product );
		}

		return is_object( $product ) && is_callable( array( $product, 'get_price' ) )
			? wc_price( $product->get_price() )
			: '';
	}

	/**
	 * Check if cart item values should include add-on prices.
	 *
	 * @param array<mixed> $cart_item Cart item.
	 * @return bool
	 */
	protected function should_add_cart_price_to_value( array $cart_item ): bool {
		/**
		 * Filters whether Product Add-ons cart item values should include add-on prices.
		 *
		 * @param bool         $add_price_to_value Whether to include add-on prices in cart item values.
		 * @param array<mixed> $cart_item          Cart item data.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( 'woocommerce_addons_add_cart_price_to_value', false, $cart_item );
	}

	/**
	 * Check if order item values should include add-on prices.
	 *
	 * @param mixed $item Order item.
	 * @return bool
	 */
	protected function should_add_order_price_to_value( $item ): bool {
		/**
		 * Filters whether Product Add-ons order item values should include add-on prices.
		 *
		 * @param bool  $add_price_to_value Whether to include add-on prices in order item values.
		 * @param mixed $item               Order item.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( 'woocommerce_addons_add_order_price_to_value', false, $item );
	}

	/**
	 * Register projected filters.
	 *
	 * @param array<int,array<string,mixed>> $filters Filter manifest.
	 */
	private function register_filter_manifest( array $filters ): void {
		foreach ( $filters as $filter ) {
			$callback = array( $this, (string) $filter['callback'] );
			if ( is_callable( $callback ) ) {
				$this->add_filter_once( (string) $filter['hook'], $callback, (int) $filter['priority'], (int) $filter['accepted_args'] );
			}
		}
	}

	/**
	 * Get a converted add-on display price.
	 *
	 * @param array<mixed> $addon Add-on data.
	 * @return float
	 */
	private function get_addon_display_price( array $addon ): float {
		$field_type = (string) ( $addon['field_type'] ?? '' );
		$value      = (float) ( $addon['value'] ?? 0 );
		$amount     = MultiCurrencyProductAddOnsCompatibilityProjectionService::get_addon_conversion_amount(
			$addon['price'] ?? 0,
			$value,
			$field_type
		);
		$converted  = $this->get_price_projection_service()->get_price( $amount, 'product' );

		return 'input_multiplier' === $field_type && 0.0 !== $value ? $converted * $value : $converted;
	}

	/**
	 * Get an add-on price for product price calculation.
	 *
	 * @param array<mixed> $addon Add-on data.
	 * @return float
	 */
	private function get_addon_calculation_price( array $addon ): float {
		$price      = (float) ( $addon['price'] ?? 0 );
		$field_type = (string) ( $addon['field_type'] ?? '' );
		$price_type = (string) ( $addon['price_type'] ?? '' );

		if ( 'percentage_based' === $price_type || 'custom_price' === $field_type ) {
			return $price;
		}

		return $this->get_addon_display_price( $addon );
	}

	/**
	 * Get an add-on price for order line item display.
	 *
	 * @param array<mixed> $addon   Add-on data.
	 * @param mixed        $product Product.
	 * @return float
	 */
	private function get_order_line_item_addon_price( array $addon, $product ): float {
		$price      = (float) ( $addon['price'] ?? 0 );
		$field_type = (string) ( $addon['field_type'] ?? '' );
		$price_type = (string) ( $addon['price_type'] ?? '' );

		if ( 'percentage_based' === $price_type && is_object( $product ) && is_callable( array( $product, 'get_price' ) ) && 0.0 !== (float) $product->get_price() ) {
			return (float) $product->get_price() * ( $price / 100 );
		}

		if ( 'custom_price' === $field_type ) {
			return $price;
		}

		return $this->get_addon_display_price( $addon );
	}

	/**
	 * Get Smart Coupons self-declared gift credit amount.
	 *
	 * @param array<mixed> $cart_item Cart item data.
	 * @return float|null
	 */
	private function get_smart_coupons_credit_amount( array $cart_item ): ?float {
		$credit_called = null;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reads third-party cart price fixture during Product Add-ons calculation.
		if ( isset( $_POST['credit_called'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized recursively by wc_clean after wp_unslash.
			$credit_called = wc_clean( wp_unslash( $_POST['credit_called'] ) );
		}

		if ( is_array( $credit_called ) ) {
			$product_id = $this->get_cart_item_product_id( $cart_item );
			if ( null !== $product_id && isset( $credit_called[ $product_id ] ) ) {
				return (float) $credit_called[ $product_id ];
			}
		}

		return ! empty( $cart_item['credit_amount'] ) ? (float) $cart_item['credit_amount'] : null;
	}

	/**
	 * Get the cart item product ID.
	 *
	 * @param array<mixed> $cart_item Cart item data.
	 * @return int|null
	 */
	private function get_cart_item_product_id( array $cart_item ): ?int {
		if ( ! isset( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) || ! is_callable( array( $cart_item['data'], 'get_id' ) ) ) {
			return null;
		}

		return (int) $cart_item['data']->get_id();
	}

	/**
	 * Get the price projection service.
	 *
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function get_price_projection_service(): MultiCurrencyPriceProjectionService {
		if ( null === $this->price_projection_service ) {
			$localization_service = new MultiCurrencyLocalizationService();

			$this->price_projection_service = new MultiCurrencyPriceProjectionService(
				wc_get_container()->get( MultiCurrencyStateBuilderFactory::class )->create( $localization_service ),
				new MultiCurrencyPriceCalculator( $localization_service )
			);
		}

		return $this->price_projection_service;
	}

	/**
	 * Register a filter only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_filter_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_filter( $hook, $callback ) ) {
			add_filter( $hook, $callback, $priority, $accepted_args );
		}
	}

	/**
	 * Register an action only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_action_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_action( $hook, $callback ) ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}
}
