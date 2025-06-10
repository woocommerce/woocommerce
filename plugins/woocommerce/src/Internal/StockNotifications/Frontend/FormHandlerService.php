<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Config;
use WC_Product;

/**
 * Class for integrating with the product page.
 */
class FormHandlerService {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'handle_signup' ) );
		add_action( 'template_redirect', array( $this, 'resume_signup' ) );
	}

	/**
	 * Handle the form submit event.
	 */
	public function handle_signup() {
		if ( ! isset( $_REQUEST['action'] ) || 'wc_bis_register' !== $_REQUEST['action'] ) { // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			return;
		}

		try {
			$data = $this->parse_request_data();

			$this->handle_signup_requirements( $data );

			$this->handle_rate_limit( $data );

			// Handle sign-up.
			// $this->handle_signup( $data );
			error_log( print_r( $data, true ) );

		} catch ( \Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
			return;
		}
	}

	/**
	 * Handle the signup resume.
	 */
	public function resume_signup() {
		if ( ! isset( $_GET['action'] ) || 'wc_bis_register_resume' !== $_GET['action'] ) { // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! is_wc_endpoint_url( 'orders' ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			$enable_myaccount_registration = get_option( 'woocommerce_enable_myaccount_registration', 'no' );

			// Notice text based on environment.
			$create_account_text = _x( ', or create a new account now.', 'back in stock form', 'woocommerce' );
			/* translators: create_account_text */
			$notice_text = sprintf( _x( 'Please log in to complete the sign-up process%s.', 'back in stock form', 'woocommerce' ), 'yes' === $enable_myaccount_registration ? $create_account_text : '' );

			wc_add_notice( $notice_text, 'notice' );
			return;
		}

		// @todo: Parse the form data.
		$this->handle_signup();
	}

	/**
	 * Handle the signup redirect.
	 *
	 * @param array $data The form data.
	 */
	private function handle_signup_requirements( $data ) {
		if ( is_user_logged_in() || ! Config::requires_account() ) {
			return;
		}

		$query_args = array_merge(
			$data,
			array(
				'action' => 'wc_bis_register_resume',
			)
		);

		$http_query_args = http_build_query( $query_args );
		$url             = sprintf( '%s%s%s', wc_get_account_endpoint_url( 'orders' ), '?', $http_query_args );
		wp_safe_redirect( $url );
		exit;
	}



	/**
	 * Check if the user is rate limited.
	 *
	 * @param array $data The form data.
	 */
	private function handle_rate_limit( $data ) {
		if ( ! Config::requires_account() ) {
			return;
		}
	}

	/**
	 * Parse the request data.
	 *
	 * @return array The parsed request data.
	 * @throws \Exception If the request data is invalid.
	 */
	private function parse_request_data() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

		$data = array();

		$data['product_id'] = isset( $_REQUEST['wc_bis_product_id'] ) ? absint( wp_unslash( $_REQUEST['wc_bis_product_id'] ) ) : false;
		if ( ! $data['product_id'] ) {
			throw new \Exception( wp_kses_post( __( 'Invalid product.', 'woocommerce' ) ) );
		}

		// Parse variation data (if any).
		$has_variation = isset( $_REQUEST['wc_bis_variation_id'] ) && ! empty( $_REQUEST['wc_bis_variation_id'] );
		if ( $has_variation ) {
			$data['variation_id'] = absint( wp_unslash( $_REQUEST['wc_bis_variation_id'] ) );
			if ( ! $data['variation_id'] ) {
				throw new \Exception( wp_kses_post( __( 'Invalid product variation.', 'woocommerce' ) ) );
			}
		}

		$is_logged_in = is_user_logged_in();
		if ( ! $is_logged_in && ! Config::requires_account() ) {
			$email = isset( $_REQUEST['wc_bis_email'] ) ? sanitize_email( wp_unslash( $_REQUEST['wc_bis_email'] ) ) : false;
			if ( ! $email ) {
				throw new \Exception( wp_kses_post( __( 'Invalid email address.', 'woocommerce' ) ) );
			}

			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				throw new \Exception( wp_kses_post( __( 'Invalid email address.', 'woocommerce' ) ) );
			}

			$data['user_id'] = 0;
			$data['email']   = $email;

			// Check if user exists with this email.
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				$data['user_id'] = $user->ID;
			}
		} else {
			$user = wp_get_current_user();
			if ( ! $user ) {
				throw new \Exception( wp_kses_post( __( 'Invalid user.', 'woocommerce' ) ) );
			}

			$data['user_id'] = $user->ID;
			$data['email']   = $user->user_email;
		}

		// Check for valid privacy terms.
		if ( ! $is_logged_in && Config::creates_account_on_signup() && ! Config::requires_account() ) {
			$opt_in = isset( $_REQUEST['wc_bis_opt_in'] ) ? wc_clean( wp_unslash( $_REQUEST['wc_bis_opt_in'] ) ) : false;
			if ( 'true' !== $opt_in ) {
				throw new \Exception( wp_kses_post( __( 'To proceed, please consent to the creation of a new account with your e-mail.', 'woocommerce' ) ) );
			}
		}

		if ( $has_variation ) {
			$posted_attributes          = array();
			$requires_posted_attributes = false;

			$product = wc_get_product( $data['product_id'] );
			if ( ! $product ) {
				throw new \Exception( wp_kses_post( __( 'Invalid product.', 'woocommerce' ) ) );
			}

			$variation = wc_get_product( $data['variation_id'] );
			if ( ! $variation ) {
				throw new \Exception( wp_kses_post( __( 'Invalid product variation.', 'woocommerce' ) ) );
			}

			// Gather posted attributes.
			foreach ( $product->get_attributes() as $attribute ) {
				if ( ! $attribute['is_variation'] ) {
					continue;
				}

				$attribute_key = 'attribute_' . sanitize_title( $attribute['name'] );

				if ( isset( $_REQUEST[ $attribute_key ] ) ) {
					if ( $attribute['is_taxonomy'] ) {
						$value = sanitize_title( wp_unslash( $_REQUEST[ $attribute_key ] ) );
					} else {
						$value = html_entity_decode( wc_clean( wp_unslash( $_REQUEST[ $attribute_key ] ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
					}

					// Don't include if it's empty.
					if ( ! empty( $value ) || '0' === $value ) {
						$posted_attributes[ $attribute_key ] = $value;
					}
				}
			}

			// Compare variation attributes and posted attributes.
			// This essentially checks if the user has selected a variation with `any` attribute.
			$variation_attributes = $variation->get_variation_attributes();
			if ( ! empty( $variation_attributes ) && ! empty( $posted_attributes ) && ! empty( array_diff( $posted_attributes, $variation_attributes ) ) ) {
				// Has `any` attribute on variation.
				$requires_posted_attributes = true;
			}

			// Return the posted attributes only if a variation with `any` attribute is detected.
			$data['posted_attributes'] = $requires_posted_attributes ? $posted_attributes : array();
		}

		// Return the validated form data.
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return $data;
	}
}
