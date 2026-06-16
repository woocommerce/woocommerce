<?php
/**
 * MultiCurrencyOrderContextService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Detects WooCommerce order contexts where order currency should be used.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyOrderContextService {

	/**
	 * Optional provider for deterministic backtrace summaries.
	 *
	 * @var callable|null
	 */
	private $backtrace_summary_provider;

	/**
	 * Constructor.
	 *
	 * @param callable|null $backtrace_summary_provider Optional backtrace summary provider.
	 */
	public function __construct( ?callable $backtrace_summary_provider = null ) {
		$this->backtrace_summary_provider = $backtrace_summary_provider;
	}

	/**
	 * Tell whether order currency should override selected currency formatting.
	 *
	 * @return bool
	 */
	public function should_use_order_currency(): bool {
		$pages = array( 'my-account', 'checkout' );
		$vars  = array( 'order-received', 'order-pay', 'orders', 'view-order' );

		if ( ! $this->is_page_with_vars( $pages, $vars ) ) {
			return false;
		}

		return $this->is_call_in_backtrace(
			array(
				'WC_Shortcode_My_Account::view_order',
				'WC_Shortcode_Checkout::order_received',
				'WC_Shortcode_Checkout::order_pay',
				'WC_Order->get_formatted_order_total',
			)
		);
	}

	/**
	 * Tell whether the current request has one of the given page and query vars.
	 *
	 * @param array<int, string> $pages Page slugs.
	 * @param array<int, string> $vars  Query var names.
	 * @return bool
	 */
	private function is_page_with_vars( array $pages, array $vars ): bool {
		global $wp;

		if ( ! $wp instanceof \WP || empty( $wp->query_vars['pagename'] ) ) {
			return false;
		}

		if ( ! in_array( $wp->query_vars['pagename'], $pages, true ) ) {
			return false;
		}

		foreach ( $vars as $var ) {
			if ( isset( $wp->query_vars[ $var ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Tell whether one of the expected calls is present in the current backtrace.
	 *
	 * @param array<int, string> $calls Expected call summaries.
	 * @return bool
	 */
	private function is_call_in_backtrace( array $calls ): bool {
		$backtrace = null === $this->backtrace_summary_provider
			? wp_debug_backtrace_summary( null, 0, false ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			: call_user_func( $this->backtrace_summary_provider );

		if ( ! is_array( $backtrace ) ) {
			return false;
		}

		foreach ( $calls as $call ) {
			if ( in_array( $call, $backtrace, true ) ) {
				return true;
			}
		}

		return false;
	}
}
