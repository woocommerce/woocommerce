<?php
/**
 * BlackboxScriptHandler class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Handles loading Blackbox JS telemetry script on payment method pages.
 *
 * Enqueues the external Blackbox JS SDK and a small initialization script
 * on checkout, pay-for-order, and add-payment-method pages. The init script
 * calls Blackbox.configure() with the site's API key and Jetpack blog ID.
 *
 * @since 10.6.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class BlackboxScriptHandler {

	/**
	 * Blackbox JS SDK URL.
	 */
	private const BLACKBOX_JS_URL = 'https://blackbox-api.wp.com/v1/dist/v.js';

	/**
	 * API key identifying WooCommerce as a Blackbox client.
	 */
	private const API_KEY = 'woocommerce';

	/**
	 * Register hooks for Blackbox script loading.
	 *
	 * Called from FraudProtectionController::on_init() which already checks
	 * if the feature is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
	}

	/**
	 * Conditionally enqueue Blackbox scripts on payment method pages.
	 *
	 * Loads scripts on checkout (including custom pages with the checkout block),
	 * pay-for-order, and add-payment-method pages.
	 * Extensions can use the `woocommerce_fraud_protection_enqueue_blackbox_scripts`
	 * filter to load scripts on additional pages (e.g., product pages for express payments).
	 *
	 * @return void
	 */
	public function maybe_enqueue_scripts(): void {
		global $wp;

		$should_enqueue = is_checkout() ||
			has_block( 'woocommerce/checkout' ) ||
			is_checkout_pay_page() ||
			// Check add-payment-method query_var to avoid loading on regular payment methods page.
			( is_add_payment_method_page() && isset( $wp->query_vars['add-payment-method'] ) );

		/**
		 * Filter whether to enqueue Blackbox fraud protection scripts on the current page.
		 *
		 * By default, scripts are loaded on checkout, pay-for-order, and add-payment-method pages.
		 * Extensions can return true to load scripts on additional pages where payment methods
		 * are rendered (e.g., product pages for express checkout buttons).
		 *
		 * @since 10.6.0
		 *
		 * @param bool $should_enqueue Whether to enqueue Blackbox scripts on the current page.
		 */
		$should_enqueue = (bool) apply_filters( 'woocommerce_fraud_protection_enqueue_blackbox_scripts', $should_enqueue );

		if ( ! $should_enqueue ) {
			return;
		}

		$blog_id = $this->get_blog_id();

		if ( ! $blog_id ) {
			FraudProtectionController::log(
				'error',
				'Blackbox scripts not loaded: Jetpack blog ID not available. Is the site connected to Jetpack?'
			);
			return;
		}

		$this->enqueue_scripts( $blog_id );
	}

	/**
	 * Enqueue the Blackbox SDK and initialization scripts.
	 *
	 * @param int $blog_id The Jetpack blog ID.
	 * @return void
	 */
	private function enqueue_scripts( int $blog_id ): void {
		wp_enqueue_script(
			'wc-fraud-protection-blackbox',
			self::BLACKBOX_JS_URL,
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- External SDK, version managed by Blackbox CDN.
			array( 'in_footer' => true )
		);

		$suffix = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';

		// Enqueue the Woo Fraud Protection init script.
		wp_enqueue_script(
			'wc-fraud-protection-blackbox-init',
			plugins_url( 'assets/js/frontend/fraud-protection/blackbox-init' . $suffix . '.js', WC_PLUGIN_FILE ),
			array( 'wc-fraud-protection-blackbox' ),
			WC_VERSION,
			array( 'in_footer' => true )
		);

		wp_localize_script(
			'wc-fraud-protection-blackbox-init',
			'wcBlackboxConfig',
			array(
				'apiKey' => self::API_KEY,
				'blogId' => $blog_id,
			)
		);
	}

	/**
	 * Get the Jetpack blog ID.
	 *
	 * @return int|false Blog ID or false if not available.
	 */
	private function get_blog_id() {
		if ( ! class_exists( \Jetpack_Options::class ) ) {
			return false;
		}

		$blog_id = \Jetpack_Options::get_option( 'id' );

		if ( ! is_numeric( $blog_id ) || (int) $blog_id <= 0 ) {
			return false;
		}

		return (int) $blog_id;
	}
}
