<?php
/**
 * MultiCurrencyRequestContext class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Detects request contexts for native multi-currency hook registration.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyRequestContext {

	/**
	 * Tell whether the current request is a frontend request.
	 *
	 * @return bool True when frontend multi-currency hooks may register.
	 *
	 * @since 11.0.0
	 */
	public function is_frontend_request(): bool {
		return ! $this->is_admin_request() && ! $this->is_cron_request() && ! $this->is_admin_api_request();
	}

	/**
	 * Tell whether the current request is Store API.
	 *
	 * @return bool True when this is a Store API request.
	 *
	 * @since 11.0.0
	 */
	public function is_store_api_request(): bool {
		$wc_store_api_request = $this->get_wc_store_api_request();
		if ( null !== $wc_store_api_request ) {
			return $wc_store_api_request;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( '' !== $request_uri && false !== strpos( $request_uri, trailingslashit( rest_get_url_prefix() ) . 'wc/store/' ) ) {
			return true;
		}

		if ( ! isset( $_GET['rest_route'] ) || ! is_string( $_GET['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		$rest_route = rawurldecode( esc_url_raw( wp_unslash( $_GET['rest_route'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 0 === strpos( $rest_route, '/wc/store/' );
	}

	/**
	 * Tell whether the current request is a Store API batch request.
	 *
	 * @return bool True when this is a Store API batch request.
	 *
	 * @since 11.0.0
	 */
	public function is_store_batch_request(): bool {
		if ( isset( $_REQUEST['rest_route'] ) && is_string( $_REQUEST['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$rest_route = sanitize_text_field( wp_unslash( $_REQUEST['rest_route'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$url_parts    = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) );
			$request_path = ! empty( $url_parts['path'] ) ? rtrim( (string) $url_parts['path'], '/' ) : '';
			$rest_route   = str_replace( trailingslashit( rest_get_url_prefix() ), '', $request_path );
		}

		if ( '' === $rest_route ) {
			return false;
		}

		return 1 === preg_match( '@^/?wc/store(/v[\d]+)?/batch@', $rest_route );
	}

	/**
	 * Tell whether the current request is an admin-originated non-Store REST request.
	 *
	 * @return bool True for admin REST requests.
	 *
	 * @since 11.0.0
	 */
	public function is_admin_api_request(): bool {
		$referer = wp_get_referer();
		if ( ! is_string( $referer ) || '' === $referer ) {
			return false;
		}

		return 0 === stripos( $referer, admin_url() ) && $this->is_wc_rest_api_request() && ! $this->is_store_api_request();
	}

	/**
	 * Tell whether frontend price/currency hooks should register.
	 *
	 * @return bool True when hooks should register.
	 *
	 * @since 11.0.0
	 */
	public function should_register_frontend_hooks(): bool {
		return $this->is_frontend_request();
	}

	/**
	 * Tell whether selected-currency writer hooks should register.
	 *
	 * @return bool True when selected-currency writers should register.
	 *
	 * @since 11.0.0
	 */
	public function should_register_selected_currency_entry_hooks(): bool {
		return $this->is_frontend_request() || $this->is_store_api_request();
	}

	/**
	 * Tell whether WordPress is serving an admin request.
	 *
	 * @return bool True for admin requests.
	 */
	protected function is_admin_request(): bool {
		return is_admin();
	}

	/**
	 * Tell whether cron is running.
	 *
	 * @return bool True for cron requests.
	 */
	protected function is_cron_request(): bool {
		return wp_doing_cron();
	}

	/**
	 * Tell whether WooCommerce considers this a REST API request.
	 *
	 * @return bool True for WooCommerce REST requests.
	 */
	protected function is_wc_rest_api_request(): bool {
		return function_exists( 'WC' ) && method_exists( WC(), 'is_rest_api_request' ) && WC()->is_rest_api_request();
	}

	/**
	 * Get WooCommerce's Store API request result when available.
	 *
	 * @return bool|null Store API result, or null when unavailable.
	 */
	protected function get_wc_store_api_request(): ?bool {
		if ( function_exists( 'WC' ) && method_exists( WC(), 'is_store_api_request' ) ) {
			return WC()->is_store_api_request();
		}

		return null;
	}
}
