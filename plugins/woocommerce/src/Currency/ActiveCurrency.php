<?php
/**
 * ActiveCurrency class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Currency;

defined( 'ABSPATH' ) || exit;

/**
 * Announces the currency and operation that are running, so any code can read them.
 *
 * WooCommerce core runs order and money operations with the order's currency active. A no-argument call such as
 * wc_get_price_decimals() made during them reports that currency. Each scope also names the operation it runs, so code
 * can see what is happening without a debug backtrace. Plugins can detect support with
 * class_exists( \Automattic\WooCommerce\Currency\ActiveCurrency::class ).
 *
 * Announcing (keep the returned scope in a local variable for as long as the work runs; releasing it restores the
 * previous state, even when an exception is thrown):
 *
 *     $scope = ActiveCurrency::scope( $currency, 'my-plugin.do_thing', $obj );
 *     // ... do the work ...
 *     $scope->release(); // Optional. The scope is also released when the variable goes out of scope.
 *
 * Reading (any code, at any depth):
 *
 *     $currency = ActiveCurrency::get(); // Null when no scope is active.
 *     if ( ActiveCurrency::is_in_operation( 'woocommerce-bookings.calculate_booking_cost' ) ) {
 *         // ... running inside that operation, at any depth.
 *     }
 *
 * Operation names:
 * - Core names are unprefixed and listed below.
 * - Extensions use `<plugin-slug>.<operation>`, for example `woocommerce-bookings.calculate_booking_cost`.
 * - Names may contain lowercase letters, digits, underscores, hyphens and dots. Other names are rejected with
 *   wc_doing_it_wrong() and the scope is created without an operation.
 * - An empty currency inherits the nearest outer currency, so an operation can be named without changing the currency.
 * - A scope may have no operation. Core opens such scopes to format a price (wc_price()), build a Store API order
 *   response and calculate shipping for a package.
 *
 * Core operations:
 * - `cart.add_to_cart` (subject: the cart)
 * - `cart.calculate_totals` (subject: the cart)
 * - `shipping.rates` (subject: the shipping method instance)
 * - `order` (subject: the order, or the parent order of a refund) is active while an order is loaded or saved, its
 *   totals, taxes, coupons or items are calculated, a refund is created, or the admin refund request runs.
 * - `product.price_for_display` (subject: the product) is active while wc_get_price_including_tax(),
 *   wc_get_price_excluding_tax() and wc_get_price_to_display() compute a price for display. Use it to tell display
 *   prices from totals, for example `is_in_operation( 'product.price_for_display' )` inside a
 *   `woocommerce_product_get_price` filter. The scope has an empty currency, so it changes no currency.
 * - `cart.product_subtotal` (subject: the cart) is active inside WC_Cart::get_product_price() and
 *   WC_Cart::get_product_subtotal(). Empty currency, like the one above.
 * - `admin.new_sales_record` (subject: null) is active in NewSalesRecord::sum_sales_for_date() and
 *   possibly_add_note(), with the store currency, since the sums there are meant in store currency.
 */
class ActiveCurrency {

	/**
	 * Scopes pushed by scope(), innermost last. Each entry holds its id, currency, operation and subject.
	 *
	 * @var array<int, array{id: int, currency: string, operation: ?string, subject: ?object}>
	 */
	private static $stack = array();

	/**
	 * Id given to the next scope, so a released scope can tell whether its stack slot is still its own.
	 *
	 * @var int
	 */
	private static $next_id = 0;

	/**
	 * Get the currency an order operation uses, or null when none is active. Fall back to the shop currency for null.
	 *
	 * An entry with an empty currency inherits the nearest outer currency.
	 *
	 * @return string|null The currency code, or null when none is active.
	 */
	public static function get(): ?string {
		foreach ( array_reverse( self::$stack ) as $entry ) {
			if ( '' !== $entry['currency'] ) {
				return $entry['currency'];
			}
		}

		return null;
	}

	/**
	 * Check whether an operation is running at any depth, so code nested inside it also matches.
	 *
	 * For example, `cart.add_to_cart` is still active inside the calculate_totals call it makes.
	 *
	 * @param string $operation Operation name, as listed in the class docblock.
	 * @return bool True when an active scope has that operation.
	 */
	public static function is_in_operation( string $operation ): bool {
		foreach ( self::$stack as $entry ) {
			if ( $operation === $entry['operation'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the subject of the nearest operation with the given name, at any depth.
	 *
	 * Helper scopes that run inside the operation, such as the one wc_price() opens, do not hide it.
	 *
	 * @since 11.4.0
	 * @param string $operation Operation name, as listed in the class docblock.
	 * @return object|null The nearest matching scope's subject, or null when no scope has that operation or it has no subject.
	 */
	public static function get_subject_of( string $operation ): ?object {
		foreach ( array_reverse( self::$stack ) as $entry ) {
			if ( $operation === $entry['operation'] ) {
				return $entry['subject'];
			}
		}

		return null;
	}

	/**
	 * Activate a currency and optional operation until the returned scope is released: explicitly with release(), when
	 * it is unset, or when it goes out of scope, including by exception.
	 *
	 * An empty code activates no currency, so the scope inherits the outer one. It still records the operation.
	 *
	 * @param string      $code      Currency code, or an empty string.
	 * @param string|null $operation Operation name, as listed in the class docblock, or null. Invalid names are
	 *                               rejected with wc_doing_it_wrong() and treated as null.
	 * @param object|null $subject   Object the operation acts on, or null.
	 * @return ActiveCurrencyScope The scope.
	 */
	public static function scope( string $code, ?string $operation = null, ?object $subject = null ): ActiveCurrencyScope {
		if ( null !== $operation && ! self::is_valid_operation( $operation ) ) {
			\wc_doing_it_wrong(
				__METHOD__,
				sprintf(
					'Invalid operation name "%s". Use lowercase letters, digits, underscores, hyphens and dots, for example "my-plugin.do_thing".',
					$operation
				),
				'11.4.0-dev'
			);
			$operation = null;
		}

		$index         = count( self::$stack );
		$id            = ++self::$next_id;
		self::$stack[] = array(
			'id'        => $id,
			'currency'  => $code,
			'operation' => $operation,
			'subject'   => $subject,
		);

		return new ActiveCurrencyScope( $index, $id );
	}

	/**
	 * Remove the entry at $index and everything pushed after it, but only if that slot still holds the scope $id.
	 *
	 * A scope released after an outer one already removed its slot does nothing, so it cannot remove entries that
	 * another scope has pushed since.
	 *
	 * @internal Used by ActiveCurrencyScope.
	 * @param int $index Stack index the scope was pushed at.
	 * @param int $id    Id the scope was given.
	 * @return void
	 */
	public static function release_entry( int $index, int $id ): void {
		if ( isset( self::$stack[ $index ] ) && $id === self::$stack[ $index ]['id'] ) {
			self::$stack = array_slice( self::$stack, 0, $index );
		}
	}

	/**
	 * Trailing argument for the formatting filters: the active currency code, or nothing when none is active.
	 *
	 * @internal
	 * @return string[] An empty array, or an array holding the active currency code.
	 */
	public static function formatting_args(): array {
		$code = self::get();

		return null === $code ? array() : array( $code );
	}

	/**
	 * Check an operation name against the allowed characters.
	 *
	 * @param string $operation Operation name.
	 * @return bool True when the name is non-empty and uses only allowed characters.
	 */
	private static function is_valid_operation( string $operation ): bool {
		return 1 === preg_match( '/^[a-z0-9][a-z0-9_.-]*\z/', $operation );
	}
}
