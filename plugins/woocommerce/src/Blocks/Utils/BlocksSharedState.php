<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Manages the registration of interactivity config and state that is commonly shared by WooCommerce blocks.
 * Initialization only happens on the first call to initialize_shared_config.
 * Intended to be used as a singleton.
 */
class BlocksSharedState {

	/**
	 * The namespace for the config.
	 *
	 * @var string
	 */
	private $settings_namespace = 'woocommerce';

	/**
	 * Whether the core config has been registered.
	 *
	 * @var boolean
	 */
	private $core_config_registered = false;

	/**
	 * Cart state.
	 *
	 * @var mixed
	 */
	private $cart;

	/**
	 * Initialize the shared core config.
	 */
	public function initialize_shared_config() {
		if ( $this->core_config_registered ) {
			return;
		}

		$this->core_config_registered = true;

		wp_interactivity_config( $this->settings_namespace, $this->get_currency_data() );
		wp_interactivity_config( $this->settings_namespace, $this->get_locale_data() );
		wp_interactivity_config( $this->settings_namespace, $this->get_core_data() );
	}

	/**
	 * Initialize interactivity state for cart that is needed by multiple blocks.
	 *
	 * @return void
	 */
	public function initialize_shared_state() {
		if ( null === $this->cart ) {
			$cart = isset( WC()->cart )
				? rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) )->data
				: array();

			wp_interactivity_state(
				'woocommerce',
				array(
					'cart'     => $cart,
					'nonce'    => wp_create_nonce( 'wc_store_api' ),
					'noticeId' => '',
					'restUrl'  => get_rest_url(),
				)
			);
		}
	}

	/**
	 * Get core data to include in settings.
	 *
	 * @return array
	 */
	protected function get_core_data() {
		return [
			'isBlockTheme' => wp_is_block_theme(),
		];
	}

	/**
	 * Get currency data to include in settings.
	 *
	 * @return array
	 */
	protected function get_currency_data() {
		$currency = get_woocommerce_currency();

		return [
			'currency' => [
				'code'              => $currency,
				'precision'         => wc_get_price_decimals(),
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
				'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
			],
		];
	}

	/**
	 * Get locale data to include in settings.
	 *
	 * @return array
	 */
	protected function get_locale_data() {
		global $wp_locale;

		return [
			'locale' => [
				'siteLocale'    => get_locale(),
				'userLocale'    => get_user_locale(),
				'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
			],
		];
	}
}
