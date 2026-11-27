<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Cart;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Explains classic cart totals that don't add up from the prices shown above them, in a tooltip on the total.
 *
 * Unit prices are rounded for display, so unit price × quantity can differ from the line total (for example £1.66 + 20%
 * VAT shows as £1.99, but three cost £5.98). The total becomes a button with an info icon that opens the explanation,
 * e.g. "Rounded to £5.98 from 3 units priced £1.992 (incl. VAT), shown as £1.99 in your cart." The Cart and Checkout
 * blocks apply the same rules in the browser and use the same markup, script and styles.
 *
 * @since 11.4.0
 */
class RoundedTotalNote implements RegisterHooksInterface {

	/**
	 * Handle of the script and style that run the tooltip, used by the classic cart and the Cart, Checkout and Mini-Cart
	 * blocks.
	 */
	public const ASSET_HANDLE = 'wc-rounded-total-info';

	/**
	 * Most decimals an exact price is shown with.
	 */
	private const MAX_EXACT_DECIMALS = 6;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_assets' ) );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'add_cart_item_subtotal_note' ), 999, 2 );
		add_filter( 'woocommerce_widget_cart_item_quantity', array( $this, 'add_mini_cart_item_quantity_note' ), 999, 2 );
		add_filter( 'woocommerce_cart_subtotal', array( $this, 'add_cart_subtotal_note' ), 999, 2 );
	}

	/**
	 * Register the tooltip script and style on init, so blocks rendered before wp_enqueue_scripts can enqueue them.
	 *
	 * @internal
	 */
	public function register_assets(): void {
		$suffix  = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
		$version = Constants::get_constant( 'WC_VERSION' );

		wp_register_script( self::ASSET_HANDLE, $this->get_asset_url( 'assets/js/frontend/rounded-total-info' . $suffix . '.js' ), array(), $version, array( 'strategy' => 'defer' ) );
		wp_register_style( self::ASSET_HANDLE, $this->get_asset_url( 'assets/css/rounded-total-info.css' ), array(), $version );
	}

	/**
	 * Get an asset URL, like WC_Frontend_Scripts::get_asset_url().
	 *
	 * @param string $path Path from the plugin folder.
	 * @return string
	 */
	private function get_asset_url( string $path ): string {
		/**
		 * Filters the asset URL.
		 *
		 * @since 3.2.0
		 *
		 * @param string $url  The asset URL.
		 * @param string $path The asset path.
		 * @return string The filtered asset URL.
		 */
		return (string) apply_filters( 'woocommerce_get_asset_url', plugins_url( $path, Constants::get_constant( 'WC_PLUGIN_FILE' ) ), $path );
	}

	/**
	 * Explain a line total on the cart page when it doesn't equal the unit price × quantity shown next to it.
	 *
	 * @param mixed $subtotal_html Line total HTML.
	 * @param mixed $cart_item     Cart item.
	 * @return mixed
	 */
	public function add_cart_item_subtotal_note( $subtotal_html, $cart_item ) {
		if ( ! is_string( $subtotal_html ) || ! is_array( $cart_item ) || ! is_cart() ) {
			return $subtotal_html;
		}

		return $this->render( $subtotal_html, $this->get_line_note( $cart_item ) );
	}

	/**
	 * Explain a Mini-Cart widget row ("3 × £1.99") when it doesn't equal the line total the cart charges.
	 *
	 * @param mixed $quantity_html Quantity × unit price HTML.
	 * @param mixed $cart_item     Cart item.
	 * @return mixed
	 */
	public function add_mini_cart_item_quantity_note( $quantity_html, $cart_item ) {
		if ( ! is_string( $quantity_html ) || ! is_array( $cart_item ) ) {
			return $quantity_html;
		}

		return $this->render( $quantity_html, $this->get_line_note( $cart_item ) );
	}

	/**
	 * Explain the cart page subtotal when it doesn't equal the sum of the line totals shown above it.
	 *
	 * @param mixed $subtotal_html Subtotal HTML.
	 * @param mixed $compound      Whether this is the compound subtotal.
	 * @return mixed
	 */
	public function add_cart_subtotal_note( $subtotal_html, $compound ) {
		if ( ! is_string( $subtotal_html ) || $compound || ! is_cart() || ! WC()->cart ) {
			return $subtotal_html;
		}

		return $this->render( $subtotal_html, $this->get_subtotal_note( WC()->cart ) );
	}

	/**
	 * Get the note for a line total that doesn't equal the shown unit price × quantity.
	 *
	 * @param array $cart_item Cart item.
	 * @return string|null Plain text note, or null when the line adds up or can't be explained simply.
	 */
	private function get_line_note( array $cart_item ): ?string {
		$product = $cart_item['data'] ?? null;
		$qty     = (float) ( $cart_item['quantity'] ?? 0 );
		$cart    = WC()->cart;

		if ( ! $product instanceof \WC_Product || $qty <= 0 || ! $cart ) {
			return null;
		}

		$incl          = $cart->display_prices_including_tax();
		$decimals      = wc_get_price_decimals();
		$price         = (float) ( $incl ? wc_get_price_including_tax( $product ) : wc_get_price_excluding_tax( $product ) );
		$unit_decimals = PriceDisplayPrecision::get_decimals( $product, $incl );
		$unit          = $this->round( $price, $unit_decimals );
		$line          = $this->round( $this->get_line_total( $product, $qty, $incl ), $decimals );
		$has_tax       = $this->to_micro( $cart_item['line_subtotal_tax'] ?? 0 ) > 0;

		if ( $this->to_micro( $unit * $qty ) === $this->to_micro( $line ) ) {
			return null;
		}

		$exact_args = array( 'round' => false );
		$exact      = (float) ( $incl ? wc_get_price_including_tax( $product, $exact_args ) : wc_get_price_excluding_tax( $product, $exact_args ) );
		$exact      = NumberUtil::round( $exact, self::MAX_EXACT_DECIMALS );
		$line_text  = $this->format( $line, $decimals );

		if ( $this->to_micro( $this->round( $exact * $qty, $decimals ) ) === $this->to_micro( $line ) ) {
			$exact_text = $this->format( $exact, $this->count_decimals( $exact ) );

			if ( $has_tax ) {
				$exact_text .= ' ' . ( $incl ? WC()->countries->inc_tax_or_vat() : WC()->countries->ex_tax_or_vat() );
			}

			if ( $this->to_micro( $exact ) === $this->to_micro( $unit ) ) {
				return sprintf(
					/* translators: 1: line total, e.g. £0.41. 2: quantity. 3: exact unit price as the cart shows it, e.g. £0.207. */
					_n( 'Rounded to %1$s from %2$s unit priced %3$s.', 'Rounded to %1$s from %2$s units priced %3$s.', (int) ceil( $qty ), 'woocommerce' ),
					$line_text,
					wc_stock_amount( $qty ),
					$exact_text
				);
			}

			return sprintf(
				/* translators: 1: line total, e.g. £5.98. 2: quantity. 3: exact unit price, e.g. "£1.992 (incl. VAT)". 4: unit price the cart shows, e.g. £1.99. */
				_n( 'Rounded to %1$s from %2$s unit priced %3$s, shown as %4$s in your cart.', 'Rounded to %1$s from %2$s units priced %3$s, shown as %4$s in your cart.', (int) ceil( $qty ), 'woocommerce' ),
				$line_text,
				wc_stock_amount( $qty ),
				$exact_text,
				$this->format( $unit, $unit_decimals )
			);
		}

		if ( ! $has_tax ) {
			return null;
		}

		return $incl
			/* translators: 1: line total, e.g. £0.74. 2: "VAT" or "Tax". */
			? sprintf( __( 'Rounded to %1$s after adding %2$s to the line.', 'woocommerce' ), $line_text, WC()->countries->tax_or_vat() )
			/* translators: 1: line total, e.g. £0.62. 2: "VAT" or "Tax". */
			: sprintf( __( 'Rounded to %1$s after taking %2$s off the line.', 'woocommerce' ), $line_text, WC()->countries->tax_or_vat() );
	}

	/**
	 * Get the note for a subtotal that doesn't equal the sum of the shown line totals.
	 *
	 * @param \WC_Cart $cart Cart.
	 * @return string|null Plain text note, or null when the subtotal adds up or can't be explained simply.
	 */
	private function get_subtotal_note( \WC_Cart $cart ): ?string {
		$incl = $cart->display_prices_including_tax();

		if ( ! $incl || 'yes' !== get_option( 'woocommerce_tax_round_at_subtotal' ) || $this->to_micro( $cart->get_subtotal_tax() ) <= 0 ) {
			return null;
		}

		$decimals = wc_get_price_decimals();
		$lines    = 0;

		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'] ?? null;

			if ( $product instanceof \WC_Product ) {
				$lines += $this->to_micro( $this->round( $this->get_line_total( $product, (float) $cart_item['quantity'], $incl ), $decimals ) );
			}
		}

		$subtotal = $this->round( (float) $cart->get_subtotal() + (float) $cart->get_subtotal_tax(), $decimals );

		return $lines === $this->to_micro( $subtotal )
			? null
			/* translators: 1: subtotal, e.g. £11.95. 2: "VAT" or "Tax". */
			: sprintf( __( 'Rounded to %1$s after adding %2$s to the subtotal.', 'woocommerce' ), $this->format( $subtotal, $decimals ), WC()->countries->tax_or_vat() );
	}

	/**
	 * Get the line total as the classic cart shows it, before rounding (see WC_Cart::get_product_subtotal()).
	 *
	 * @param \WC_Product $product Product.
	 * @param float       $qty     Quantity.
	 * @param bool        $incl    Whether the cart shows prices including tax.
	 * @return float
	 */
	private function get_line_total( \WC_Product $product, float $qty, bool $incl ): float {
		if ( ! $product->is_taxable() ) {
			return (float) $product->get_price() * $qty;
		}

		$args = array( 'qty' => $qty );

		return (float) ( $incl ? wc_get_price_including_tax( $product, $args ) : wc_get_price_excluding_tax( $product, $args ) );
	}

	/**
	 * Make an amount a button, with an info icon before it, that opens the note in a tooltip.
	 *
	 * The tooltip is a native popover; wc-rounded-total-info.js also opens it on hover and focus and places it. The
	 * amount stays outside the button when it contains links or form fields, which a button can't contain.
	 *
	 * @param string      $amount_html Amount HTML.
	 * @param string|null $note        Plain text note.
	 * @return string
	 */
	private function render( string $amount_html, ?string $note ): string {
		if ( null === $note ) {
			return $amount_html;
		}

		wp_enqueue_script( self::ASSET_HANDLE );
		wp_enqueue_style( self::ASSET_HANDLE );

		$id          = wp_unique_id( 'wc-rounded-total-info-' );
		$amount_safe = ! $this->has_interactive_content( $amount_html );

		return sprintf(
			'<span class="wc-rounded-total-info"><button type="button" class="wc-rounded-total-info__button" popovertarget="%1$s" popovertargetaction="show" aria-expanded="false">%2$s%3$s<span class="wc-rounded-total-info__label">%4$s</span></button><span id="%1$s" class="wc-rounded-total-info__tooltip" popover="auto" role="tooltip">%5$s</span>%6$s</span>',
			esc_attr( $id ),
			self::get_icon_html(),
			$amount_safe ? '<span class="wc-rounded-total-info__amount">' . $amount_html . '</span>' : '',
			esc_html__( 'Rounding details', 'woocommerce' ),
			esc_html( $note ),
			$amount_safe ? '' : $amount_html
		);
	}

	/**
	 * The info icon shown before a rounded total, also used by the Mini-Cart block.
	 *
	 * @internal
	 * @return string SVG markup.
	 */
	public static function get_icon_html(): string {
		return '<svg class="wc-rounded-total-info__icon" viewBox="0 0 16 16" width="16" height="16" fill="none" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/><path d="M8 7.2v4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="8" cy="4.9" r="0.9" fill="currentColor"/></svg>';
	}

	/**
	 * Whether HTML contains elements a button can't contain, such as a link another plugin added to a total.
	 *
	 * @param string $html HTML.
	 * @return bool
	 */
	private function has_interactive_content( string $html ): bool {
		$processor = new \WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag() ) {
			if ( in_array( $processor->get_tag(), array( 'A', 'BUTTON', 'DETAILS', 'IFRAME', 'INPUT', 'LABEL', 'SELECT', 'TEXTAREA' ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Round an amount without float noise deciding the result, like the cart totals.
	 *
	 * @param float $amount   Amount.
	 * @param int   $decimals Decimals.
	 * @return float
	 */
	private function round( float $amount, int $decimals ): float {
		return (float) NumberUtil::round( NumberUtil::round( $amount, wc_get_rounding_precision() ), $decimals );
	}

	/**
	 * Convert an amount to an integer at the rounding precision, so amounts with different decimals compare exactly.
	 *
	 * @param mixed $amount Amount.
	 * @return int
	 */
	private function to_micro( $amount ): int {
		return (int) NumberUtil::round( (float) $amount * ( 10 ** wc_get_rounding_precision() ), 0 );
	}

	/**
	 * Count the decimals an exact price needs: trailing zeros removed, at least the store's decimals, at most 6.
	 *
	 * @param float $amount Amount, rounded to 6 decimals.
	 * @return int
	 */
	private function count_decimals( float $amount ): int {
		$digits = rtrim( number_format( $amount, self::MAX_EXACT_DECIMALS, '.', '' ), '0' );

		return max( wc_get_price_decimals(), strlen( substr( (string) strrchr( $digits, '.' ), 1 ) ) );
	}

	/**
	 * Format an amount as plain text.
	 *
	 * @param float $amount   Amount.
	 * @param int   $decimals Decimals.
	 * @return string
	 */
	private function format( float $amount, int $decimals ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount, array( 'decimals' => $decimals ) ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
}
