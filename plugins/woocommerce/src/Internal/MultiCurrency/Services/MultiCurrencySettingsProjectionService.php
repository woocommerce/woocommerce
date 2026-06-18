<?php
/**
 * MultiCurrencySettingsProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency settings metadata without registering settings pages.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySettingsProjectionService {

	private const SETTINGS_TAB          = 'wcpay_multi_currency';
	private const SETTINGS_SCREEN_BASE  = 'woocommerce_page_wc-settings';
	private const SETTINGS_FIELD_TYPE   = 'wcpay_multi_currency_settings_page';
	private const ONBOARDING_FIELD_TYPE = 'wcpay_currencies_settings_onboarding_cta';
	private const SETTINGS_CONTAINER_ID = 'wcpay_multi_currency_settings_container';
	private const ONBOARDING_CTA_ID     = 'wcpay_enabled_currencies_onboarding_cta';
	private const ENABLED_CURRENCIES_ID = 'wcpay_multi_currency_enabled_currencies';
	private const ADMIN_SCRIPT_ENTRY    = 'multi-currency-settings';
	private const ADMIN_ASSET_HANDLE    = 'wc-admin-multi-currency-settings';
	private const LEARN_MORE_URL        = 'https://woocommerce.com/document/woopayments/currencies/multi-currency-setup/';

	/**
	 * Project the multi-currency settings page manifest.
	 *
	 * @param bool $provider_connected Whether a payments provider is connected.
	 * @param bool $is_cli             Whether the current request is WP-CLI.
	 * @param bool $is_wpcom_jobs      Whether the current request is a WPCOM jobs request.
	 * @param bool $did_upgrade        Whether an upgrader completion action ran.
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function get_settings_page_manifest(
		bool $provider_connected,
		bool $is_cli,
		bool $is_wpcom_jobs,
		bool $did_upgrade
	): array {
		if ( $is_cli || $is_wpcom_jobs || $did_upgrade ) {
			return array();
		}

		return array(
			'id'               => self::SETTINGS_TAB,
			'label'            => self::get_tab_label(),
			'mode'             => $provider_connected ? 'settings' : 'onboarding_cta',
			'hide_save_button' => true,
			'settings'         => $provider_connected ? self::get_connected_settings() : self::get_onboarding_settings(),
		);
	}

	/**
	 * Project settings page hook metadata.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'actions' => array(
				array(
					'hook'     => 'admin_print_scripts',
					'callback' => 'maybe_add_print_emoji_detection_script',
					'priority' => 10,
				),
				array(
					'hook'     => 'woocommerce_admin_field_wcpay_multi_currency_settings_page',
					'callback' => 'render_settings_container',
					'priority' => 10,
				),
				array(
					'hook'     => 'woocommerce_admin_field_wcpay_currencies_settings_onboarding_cta',
					'callback' => 'render_onboarding_cta',
					'priority' => 10,
				),
			),
		);
	}

	/**
	 * Project the React settings container markup.
	 *
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function get_settings_container_markup(): string {
		return sprintf(
			'<div id="%s" class="wc-settings-prevent-change-event" aria-describedby="%s-description"></div>',
			esc_attr( self::SETTINGS_CONTAINER_ID ),
			esc_attr( self::SETTINGS_CONTAINER_ID )
		);
	}

	/**
	 * Project onboarding CTA settings rows.
	 *
	 * @return array<int,array<string,string>>
	 *
	 * @since 11.0.0
	 */
	public static function get_onboarding_settings(): array {
		return array(
			array(
				'title' => __( 'Enabled currencies', 'woocommerce' ),
				'desc'  => sprintf(
					/* translators: %s: URL to the multi-currency documentation. */
					__( 'Accept payments in multiple currencies. Prices are converted based on exchange rates and rounding rules. <a href="%s">Learn more</a>', 'woocommerce' ),
					esc_url( self::LEARN_MORE_URL )
				),
				'type'  => 'title',
				'id'    => self::ENABLED_CURRENCIES_ID,
			),
			array(
				'type' => self::ONBOARDING_FIELD_TYPE,
			),
			array(
				'type' => 'sectionend',
				'id'   => self::ENABLED_CURRENCIES_ID,
			),
		);
	}

	/**
	 * Project onboarding CTA markup.
	 *
	 * @param string $onboarding_url Provider onboarding URL.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function get_onboarding_cta_markup( string $onboarding_url ): string {
		return sprintf(
			'<div><p>%s</p><a href="%s" id="%s" type="button" class="button-primary">%s</a></div>',
			self::get_onboarding_message(),
			esc_url( $onboarding_url ),
			esc_attr( self::ONBOARDING_CTA_ID ),
			esc_html__( 'Get started', 'woocommerce' )
		);
	}

	/**
	 * Project whether the current admin screen is the multi-currency settings page.
	 *
	 * @param bool        $is_admin    Whether the current request is admin.
	 * @param string|null $current_tab Current settings tab.
	 * @param string|null $screen_base Current screen base.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function is_multi_currency_settings_page( bool $is_admin, ?string $current_tab, ?string $screen_base ): bool {
		return $is_admin
			&& self::SETTINGS_TAB === $current_tab
			&& self::SETTINGS_SCREEN_BASE === $screen_base;
	}

	/**
	 * Project whether admin assets should enqueue.
	 *
	 * @param string|null $current_tab Current settings tab.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_enqueue_admin_assets( ?string $current_tab ): bool {
		return self::SETTINGS_TAB === $current_tab;
	}

	/**
	 * Project admin asset registration metadata.
	 *
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function get_admin_asset_manifest(): array {
		return array(
			'script' => array(
				'entry'  => self::ADMIN_SCRIPT_ENTRY,
				'handle' => self::ADMIN_ASSET_HANDLE,
			),
			'style'  => array(
				'entry'  => self::ADMIN_SCRIPT_ENTRY,
				'file'   => 'style',
				'handle' => self::ADMIN_ASSET_HANDLE,
			),
		);
	}

	/**
	 * Project the multi-currency flag into the WCPay JS config.
	 *
	 * @param array<string,mixed> $config Existing config.
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function add_props_to_wcpay_js_config( array $config ): array {
		$config['isMultiCurrencyEnabled'] = true;

		return $config;
	}

	/**
	 * Get connected settings rows.
	 *
	 * @return array<int,array<string,string>>
	 */
	private static function get_connected_settings(): array {
		return array(
			array(
				'type' => self::SETTINGS_FIELD_TYPE,
			),
		);
	}

	/**
	 * Get the settings tab label.
	 *
	 * @return string
	 */
	private static function get_tab_label(): string {
		return _x( 'Multi-currency', 'Settings tab label', 'woocommerce' );
	}

	/**
	 * Get the onboarding CTA message.
	 *
	 * @return string
	 */
	private static function get_onboarding_message(): string {
		return sprintf(
			/* translators: %s: WooPayments. */
			esc_html__( 'To add new currencies to your store, please finish setting up %s.', 'woocommerce' ),
			'WooPayments'
		);
	}
}
