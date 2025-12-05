<?php
/**
 * SessionDataCollector class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Collects session data for fraud protection API requests.
 *
 * Centralizes all session/event data collection with a flat schema
 * matching the WPCOM fraud protection endpoint format.
 *
 * @since 10.5.0
 */
class SessionDataCollector {

	/**
	 * Collect all session data for fraud protection API.
	 *
	 * Returns a flat array matching the WPCOM endpoint schema.
	 * Fields that are not available will be null.
	 *
	 * @return array<string, string|null> Session data array.
	 */
	public function collect(): array {
		return array(
			// Required fields.
			'session_id'          => $this->get_session_id(),
			'ip_address'          => $this->get_client_ip(),
			'event_timestamp'     => gmdate( 'Y-m-d H:i:s' ),

			// Optional session fields.
			'email'               => $this->get_customer_email(),
			'ja3_hash'            => null, // Not available at PHP level - requires edge infrastructure.
			'user_agent'          => $this->get_user_agent(),
			'billing_country'     => $this->get_billing_country(),
			'cart_hash'           => $this->get_cart_hash(),

			// Payment fields - null for non-payment events.
			'customer_id'         => null,
			'payment_method_type' => null,
			'card_bin'            => null,
			'card_last4'          => null,
			'card_brand'          => null,
			'payer_id'            => null,
			'outcome'             => null,
			'decline_reason'      => null,
		);
	}

	/**
	 * Get the session ID.
	 *
	 * Uses the WooCommerce customer ID which uniquely identifies the session.
	 * For logged-in users this is the user ID, for guests it's a random `t_{hash}` value.
	 *
	 * @return string|null Session ID or null if not available.
	 */
	private function get_session_id(): ?string {
		$this->ensure_session_available();

		if ( ! isset( WC()->session ) || ! WC()->session instanceof \WC_Session ) {
			return null;
		}

		$customer_id = WC()->session->get_customer_id();
		if ( $customer_id ) {
			return (string) $customer_id;
		}

		return null;
	}

	/**
	 * Get customer email address.
	 *
	 * Checks logged-in user email first, then session customer data.
	 *
	 * @return string|null Email address or null if not available.
	 */
	private function get_customer_email(): ?string {
		// Try logged-in user first.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( $user && $user->user_email ) {
				return $user->user_email;
			}
		}

		// Try session customer data.
		$this->ensure_session_available();

		if ( isset( WC()->session ) && WC()->session instanceof \WC_Session ) {
			$customer_data = WC()->session->get( 'customer' );
			if ( is_array( $customer_data ) && ! empty( $customer_data['email'] ) ) {
				return $customer_data['email'];
			}
		}

		// Try WC customer object.
		if ( isset( WC()->customer ) && WC()->customer instanceof \WC_Customer ) {
			$email = WC()->customer->get_billing_email();
			if ( $email ) {
				return $email;
			}
		}

		return null;
	}

	/**
	 * Get the client IP address.
	 *
	 * Supports proxy headers (X-Forwarded-For, X-Real-IP).
	 *
	 * @return string|null IP address or null if not available.
	 */
	private function get_client_ip(): ?string {
		// Check X-Forwarded-For header (may contain multiple IPs).
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ip = explode( ',', $ip );
			$ip = trim( $ip[0] );
			if ( $ip ) {
				return $ip;
			}
		}

		// Check X-Real-IP header.
		if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
			if ( $ip ) {
				return $ip;
			}
		}

		// Fall back to REMOTE_ADDR.
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			if ( $ip ) {
				return $ip;
			}
		}

		return null;
	}

	/**
	 * Get the user agent string.
	 *
	 * @return string|null User agent or null if not available.
	 */
	private function get_user_agent(): ?string {
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		return null;
	}

	/**
	 * Get the billing country code.
	 *
	 * @return string|null Billing country code or null if not available.
	 */
	private function get_billing_country(): ?string {
		// Try WC customer object first.
		if ( isset( WC()->customer ) && WC()->customer instanceof \WC_Customer ) {
			$country = WC()->customer->get_billing_country();
			if ( $country ) {
				return $country;
			}
		}

		// Try session customer data.
		$this->ensure_session_available();

		if ( isset( WC()->session ) && WC()->session instanceof \WC_Session ) {
			$customer_data = WC()->session->get( 'customer' );
			if ( is_array( $customer_data ) && ! empty( $customer_data['country'] ) ) {
				return $customer_data['country'];
			}
		}

		return null;
	}

	/**
	 * Get the cart hash.
	 *
	 * @return string|null Cart hash or null if cart is empty or not available.
	 */
	private function get_cart_hash(): ?string {
		$this->ensure_session_available();

		if ( isset( WC()->cart ) && WC()->cart instanceof \WC_Cart ) {
			$hash = WC()->cart->get_cart_hash();
			if ( $hash ) {
				return $hash;
			}
		}

		return null;
	}

	/**
	 * Ensure WooCommerce session is available.
	 *
	 * Loads cart if not already loaded, which initializes session for both
	 * traditional (cookie) and Store API (token) flows.
	 *
	 * @return void
	 */
	private function ensure_session_available(): void {
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
	}
}
