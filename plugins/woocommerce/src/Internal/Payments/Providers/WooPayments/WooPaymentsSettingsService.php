<?php
/**
 * WooPaymentsSettingsService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Throwable;
use WP_Error;
use WP_REST_Request;

/**
 * Provides the native WooPayments settings contract consumed by the hoisted settings store.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native WooPayments settings runtime.
 */
class WooPaymentsSettingsService {

	private const SETTINGS_OPTION = 'woocommerce_woocommerce_payments_settings';

	private const MULTI_CURRENCY_FLAG_OPTION = '_wcpay_feature_customer_multi_currency';

	private const WCPAY_SUBSCRIPTIONS_FLAG_OPTION = '_wcpay_feature_subscriptions';

	private const SUPPORTED_PAYMENT_METHOD_IDS = array(
		'alipay',
		'amazon_pay',
		'apple_pay',
		'au_becs_debit',
		'bancontact',
		'card',
		'eps',
		'giropay',
		'google_pay',
		'grabpay',
		'ideal',
		'jcb',
		'klarna',
		'link',
		'multibanco',
		'p24',
		'sepa_debit',
		'sofort',
		'wechat_pay',
		'affirm',
		'afterpay_clearpay',
	);

	private const MANUAL_CAPTURE_PAYMENT_METHOD_IDS = array(
		'amazon_pay',
		'apple_pay',
		'card',
		'google_pay',
		'link',
	);

	private const EXPRESS_CHECKOUT_METHOD_IDS = array(
		'payment_request',
		'amazon_pay',
		'woopay',
		'link',
	);

	private const PAYMENT_METHOD_CAPABILITY_KEY_MAP = array(
		'alipay'            => 'alipay_payments',
		'amazon_pay'        => 'amazon_pay_payments',
		'apple_pay'         => 'card_payments',
		'au_becs_debit'     => 'au_becs_debit_payments',
		'bancontact'        => 'bancontact_payments',
		'card'              => 'card_payments',
		'eps'               => 'eps_payments',
		'giropay'           => 'giropay_payments',
		'google_pay'        => 'card_payments',
		'grabpay'           => 'grabpay_payments',
		'ideal'             => 'ideal_payments',
		'jcb'               => 'jcb_payments',
		'klarna'            => 'klarna_payments',
		'link'              => 'link_payments',
		'multibanco'        => 'multibanco_payments',
		'p24'               => 'p24_payments',
		'sepa_debit'        => 'sepa_debit_payments',
		'sofort'            => 'sofort_payments',
		'wechat_pay'        => 'wechat_pay_payments',
		'affirm'            => 'affirm_payments',
		'afterpay_clearpay' => 'afterpay_clearpay_payments',
	);

	private const DUPLICATE_PAYMENT_METHOD_KEYWORDS = array(
		'card'              => array( 'credit_card', 'creditcard', 'cc', 'card', 'stripe', 'woocommerce_payments' ),
		'alipay'            => array( 'alipay' ),
		'amazon_pay'        => array( 'amazon_pay', 'amazonpay' ),
		'au_becs_debit'     => array( 'au_becs', 'becs' ),
		'bancontact'        => array( 'bancontact' ),
		'eps'               => array( 'eps' ),
		'giropay'           => array( 'giropay' ),
		'grabpay'           => array( 'grabpay' ),
		'ideal'             => array( 'ideal' ),
		'klarna'            => array( 'klarna' ),
		'multibanco'        => array( 'multibanco' ),
		'p24'               => array( 'p24', 'przelewy24' ),
		'sepa_debit'        => array( 'sepa' ),
		'sofort'            => array( 'sofort' ),
		'wechat_pay'        => array( 'wechat' ),
		'affirm'            => array( 'affirm' ),
		'afterpay_clearpay' => array( 'afterpay', 'clearpay' ),
	);

	private const PAYMENT_REQUEST_DUPLICATE_METHOD_ID = 'apple_pay_google_pay';

	private const FILTER_GATEWAY_DUPLICATE_PAYMENT_METHOD_IDS = 'woocommerce_native_woopayments_gateway_duplicate_payment_method_ids';

	private const PAYMENT_REQUEST_DUPLICATE_GATEWAY_KEYWORDS = array(
		'apple_pay',
		'applepay',
		'google_pay',
		'googlepay',
	);

	private const ALLOWED_OPTIONS = array(
		'wcpay_multi_currency_setup_completed'             => 'bool',
		'woocommerce_dismissed_todo_tasks'                 => 'array',
		'woocommerce_remind_me_later_todo_tasks'           => 'array',
		'woocommerce_deleted_todo_tasks'                   => 'array',
		'wcpay_fraud_protection_welcome_tour_dismissed'    => 'bool',
		'wcpay_onboarding_eligibility_modal_dismissed'     => 'bool',
		'wcpay_connection_success_modal_dismissed'         => 'bool',
		'wcpay_next_deposit_notice_dismissed'              => 'bool',
		'wcpay_duplicate_payment_method_notices_dismissed' => 'array',
		'wcpay_instant_deposit_notice_dismissed'           => 'bool',
		'wcpay_exit_survey_last_shown'                     => 'string',
	);

	private const LOCAL_SETTING_MAP = array(
		'is_wcpay_enabled'                               => array( 'enabled', 'bool' ),
		'is_manual_capture_enabled'                      => array( 'manual_capture', 'bool' ),
		'is_test_mode_enabled'                           => array( 'test_mode', 'bool' ),
		'is_debug_log_enabled'                           => array( 'enable_logging', 'bool' ),
		'is_saved_cards_enabled'                         => array( 'saved_cards', 'bool' ),
		'is_payment_request_enabled'                     => array( 'payment_request', 'bool' ),
		'is_express_checkout_in_payment_methods_enabled' => array( 'express_checkout_in_payment_methods', 'bool' ),
		'payment_request_button_size'                    => array( 'payment_request_button_size', 'string' ),
		'payment_request_button_type'                    => array( 'payment_request_button_type', 'string' ),
		'payment_request_button_theme'                   => array( 'payment_request_button_theme', 'string' ),
		'payment_request_button_border_radius'           => array( 'payment_request_button_border_radius', 'int' ),
		'is_woopay_enabled'                              => array( 'platform_checkout', 'bool' ),
		'is_woopay_global_theme_support_enabled'         => array( 'is_woopay_global_theme_support_enabled', 'bool' ),
		'woopay_store_logo'                              => array( 'platform_checkout_store_logo', 'string' ),
	);

	private const ACCOUNT_SETTING_MAP = array(
		'account_statement_descriptor'       => 'statement_descriptor',
		'account_statement_descriptor_kanji' => 'statement_descriptor_kanji',
		'account_statement_descriptor_kana'  => 'statement_descriptor_kana',
		'account_business_name'              => 'business_name',
		'account_business_url'               => 'business_url',
		'account_business_support_address'   => 'business_support_address',
		'account_business_support_email'     => 'business_support_email',
		'account_business_support_phone'     => 'business_support_phone',
		'account_branding_logo'              => 'branding_logo',
		'account_branding_icon'              => 'branding_icon',
		'account_branding_primary_color'     => 'branding_primary_color',
		'account_branding_secondary_color'   => 'branding_secondary_color',
		'account_communications_email'       => 'communications_email',
		'deposit_schedule_interval'          => 'deposit_schedule_interval',
		'deposit_schedule_monthly_anchor'    => 'deposit_schedule_monthly_anchor',
		'deposit_schedule_weekly_anchor'     => 'deposit_schedule_weekly_anchor',
	);

	/**
	 * Native WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * WooPay session service.
	 *
	 * @var WooPaymentsWooPaySessionService|null
	 */
	private ?WooPaymentsWooPaySessionService $woopay_session_service = null;

	/**
	 * Payment method promotions service.
	 *
	 * @var WooPaymentsPmPromotionsService|null
	 */
	private ?WooPaymentsPmPromotionsService $pm_promotions_service = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsAccountService            $account_service        Native WooPayments account service.
	 * @param WooPaymentsApiClient                 $api_client             Native WooPayments API client.
	 * @param WooPaymentsWooPaySessionService|null $woopay_session_service Optional WooPay session service.
	 * @param WooPaymentsPmPromotionsService|null  $pm_promotions_service  Optional PM promotions service.
	 */
	final public function init( WooPaymentsAccountService $account_service, WooPaymentsApiClient $api_client, ?WooPaymentsWooPaySessionService $woopay_session_service = null, ?WooPaymentsPmPromotionsService $pm_promotions_service = null ): void {
		$this->account_service        = $account_service;
		$this->api_client             = $api_client;
		$this->woopay_session_service = $woopay_session_service;
		$this->pm_promotions_service  = $pm_promotions_service;
	}

	/**
	 * Get payment method IDs accepted by the settings REST contract.
	 *
	 * @return string[]
	 */
	public static function get_supported_payment_method_ids(): array {
		return self::SUPPORTED_PAYMENT_METHOD_IDS;
	}

	/**
	 * Get express checkout method IDs accepted by the settings REST contract.
	 *
	 * @return string[]
	 */
	public static function get_express_checkout_method_ids(): array {
		return self::EXPRESS_CHECKOUT_METHOD_IDS;
	}

	/**
	 * Get the native WooPayments settings contract.
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings(): array {
		$settings                     = $this->get_gateway_settings();
		$account_fields               = $this->get_account_backed_response_fields( $settings );
		$available_payment_method_ids = $this->get_available_payment_method_ids( $settings );
		$enabled_payment_method_ids   = $this->sanitize_payment_method_ids(
			$this->get_array_setting( $settings, 'upe_enabled_payment_method_ids', array( 'card' ) ),
			$available_payment_method_ids
		);

		return array(
			'enabled_payment_method_ids'                 => $enabled_payment_method_ids,
			'available_payment_method_ids'               => $available_payment_method_ids,
			'payment_method_statuses'                    => $this->get_payment_method_statuses(),
			'duplicated_payment_method_ids'              => $this->get_duplicated_payment_method_ids(),
			'dismissed_duplicate_payment_method_notices' => $this->get_dismissed_duplicate_payment_method_notices(),
			'account_fees'                               => $this->get_account_fees(),
			'pm_promotions'                              => $this->get_pm_promotions_service()->get_visible_promotions() ?? array(),
			'is_wcpay_enabled'                           => $this->is_yes( $settings['enabled'] ?? 'no' ),
			'is_manual_capture_enabled'                  => $this->is_yes( $settings['manual_capture'] ?? 'no' ),
			'is_test_mode_enabled'                       => $this->account_service->is_test_mode_enabled(),
			'is_test_mode_onboarding'                    => $this->account_service->is_test_mode_onboarding_enabled(),
			'is_dev_mode_enabled'                        => $this->account_service->is_dev_mode_enabled(),
			'is_multi_currency_enabled'                  => '1' === (string) get_option( self::MULTI_CURRENCY_FLAG_OPTION, '0' ),
			'is_wcpay_subscriptions_enabled'             => '1' === (string) get_option( self::WCPAY_SUBSCRIPTIONS_FLAG_OPTION, '0' ),
			'is_wcpay_subscriptions_eligible'            => $this->is_subscriptions_eligible(),
			'is_subscriptions_plugin_active'             => class_exists( 'WC_Subscriptions' ),
			'account_country'                            => $account_fields['account_country'],
			'account_statement_descriptor'               => $account_fields['account_statement_descriptor'],
			'account_statement_descriptor_kanji'         => $account_fields['account_statement_descriptor_kanji'],
			'account_statement_descriptor_kana'          => $account_fields['account_statement_descriptor_kana'],
			'account_business_name'                      => $account_fields['account_business_name'],
			'account_business_url'                       => $account_fields['account_business_url'],
			'account_business_support_address'           => $account_fields['account_business_support_address'],
			'account_business_support_email'             => $account_fields['account_business_support_email'],
			'account_business_support_phone'             => $account_fields['account_business_support_phone'],
			'account_branding_logo'                      => $account_fields['account_branding_logo'],
			'account_branding_icon'                      => $account_fields['account_branding_icon'],
			'account_branding_primary_color'             => $account_fields['account_branding_primary_color'],
			'account_branding_secondary_color'           => $account_fields['account_branding_secondary_color'],
			'account_domestic_currency'                  => $account_fields['account_domestic_currency'],
			'account_communications_email'               => $account_fields['account_communications_email'],
			'is_payment_request_enabled'                 => $this->is_yes( $settings['payment_request'] ?? 'yes' ),
			'is_express_checkout_in_payment_methods_enabled' => $this->is_yes( $settings['express_checkout_in_payment_methods'] ?? 'no' ),
			'is_express_checkout_in_payment_methods_list_supported' => true,
			'is_debug_log_enabled'                       => $this->is_yes( $settings['enable_logging'] ?? 'no' ),
			'payment_request_button_size'                => $this->get_string_setting( $settings, 'payment_request_button_size', 'medium' ),
			'payment_request_button_type'                => $this->get_string_setting( $settings, 'payment_request_button_type', 'default' ),
			'payment_request_button_theme'               => $this->get_string_setting( $settings, 'payment_request_button_theme', 'dark' ),
			'payment_request_button_border_radius'       => $this->get_int_setting( $settings, 'payment_request_button_border_radius', 4 ),
			'is_saved_cards_enabled'                     => $this->is_yes( $settings['saved_cards'] ?? 'yes' ),
			'is_card_present_eligible'                   => false,
			'is_woopay_enabled'                          => $this->is_yes( $settings['platform_checkout'] ?? 'no' ),
			'woopay_last_disable_date'                   => $this->get_string_setting( $settings, 'platform_checkout_last_disable_date' ),
			'is_woopay_global_theme_support_enabled'     => $this->is_yes( $settings['is_woopay_global_theme_support_enabled'] ?? 'no' ),
			'is_woopay_global_theme_support_eligible'    => $this->is_woopay_global_theme_support_eligible(),
			'show_woopay_incompatibility_notice'         => (bool) get_option( 'woopay_invalid_extension_found', false ),
			'woopay_custom_message'                      => $this->get_string_setting( $settings, 'platform_checkout_custom_message' ),
			'woopay_store_logo'                          => $this->get_string_setting( $settings, 'platform_checkout_store_logo' ),
			'woopay_appearance'                          => $this->get_woopay_appearance_for_settings(),
			'woopay_font_rules'                          => $this->get_woopay_font_rules_for_settings(),
			'store_name'                                 => get_bloginfo( 'name' ),
			'store_currency'                             => $this->get_store_currency(),
			'site_logo_url'                              => $this->get_site_logo_url(),
			'deposit_schedule_interval'                  => $account_fields['deposit_schedule_interval'],
			'deposit_schedule_monthly_anchor'            => $account_fields['deposit_schedule_monthly_anchor'],
			'deposit_schedule_weekly_anchor'             => $account_fields['deposit_schedule_weekly_anchor'],
			'deposit_delay_days'                         => $account_fields['deposit_delay_days'],
			'deposit_status'                             => $account_fields['deposit_status'],
			'deposit_restrictions'                       => $account_fields['deposit_restrictions'],
			'deposit_completed_waiting_period'           => $account_fields['deposit_completed_waiting_period'],
			'current_protection_level'                   => $this->get_current_protection_level(),
			'advanced_fraud_protection_settings'         => $this->get_advanced_fraud_protection_settings( $settings ),
			'fraud_protection'                           => $this->get_fraud_protection_settings(),
			'fraud_protection_allowed_countries'         => $this->get_fraud_protection_allowed_countries(),
			'is_fraud_protection_review_feature_active'  => $this->is_fraud_protection_review_feature_active(),
			'express_checkout_product_methods'           => $this->sanitize_payment_method_ids( $this->get_array_setting( $settings, 'express_checkout_product_methods' ), self::EXPRESS_CHECKOUT_METHOD_IDS ),
			'express_checkout_cart_methods'              => $this->sanitize_payment_method_ids( $this->get_array_setting( $settings, 'express_checkout_cart_methods' ), self::EXPRESS_CHECKOUT_METHOD_IDS ),
			'express_checkout_checkout_methods'          => $this->sanitize_payment_method_ids( $this->get_array_setting( $settings, 'express_checkout_checkout_methods' ), self::EXPRESS_CHECKOUT_METHOD_IDS ),
		);
	}

	/**
	 * Tell whether the connected account can use WooPay global theme support.
	 *
	 * @return bool
	 */
	private function is_woopay_global_theme_support_eligible(): bool {
		$account_data = $this->account_service->get_cached_account_data();

		return ! empty( $account_data['platform_global_theme_support_enabled'] );
	}

	/**
	 * Get WooPay appearance data for the settings preview.
	 *
	 * @return array<string,mixed>
	 */
	private function get_woopay_appearance_for_settings(): array {
		$service = $this->get_woopay_session_service();

		return $service instanceof WooPaymentsWooPaySessionService ? $service->get_woopay_appearance() : array();
	}

	/**
	 * Get WooPay font rules for the settings preview.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function get_woopay_font_rules_for_settings(): array {
		$service = $this->get_woopay_session_service();

		return $service instanceof WooPaymentsWooPaySessionService ? $service->get_woopay_font_rules() : array();
	}

	/**
	 * Get the site's logo URL for the settings preview.
	 *
	 * @return string
	 */
	private function get_site_logo_url(): string {
		$logo_id = function_exists( 'get_theme_mod' ) ? get_theme_mod( 'custom_logo' ) : 0;

		if ( ! $logo_id || ! function_exists( 'wp_get_attachment_image_url' ) ) {
			return '';
		}

		$url = wp_get_attachment_image_url( (int) $logo_id, 'full' );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Get the WooPay session service when it is available.
	 *
	 * @return WooPaymentsWooPaySessionService|null
	 */
	private function get_woopay_session_service(): ?WooPaymentsWooPaySessionService {
		if ( $this->woopay_session_service instanceof WooPaymentsWooPaySessionService ) {
			return $this->woopay_session_service;
		}

		if ( ! function_exists( 'wc_get_container' ) ) {
			return null;
		}

		try {
			$service = wc_get_container()->get( WooPaymentsWooPaySessionService::class );
		} catch ( Throwable $e ) {
			return null;
		}

		if ( ! $service instanceof WooPaymentsWooPaySessionService ) {
			return null;
		}

		$this->woopay_session_service = $service;

		return $this->woopay_session_service;
	}

	/**
	 * Get the payment method promotions service.
	 *
	 * @return WooPaymentsPmPromotionsService
	 */
	private function get_pm_promotions_service(): WooPaymentsPmPromotionsService {
		if ( $this->pm_promotions_service instanceof WooPaymentsPmPromotionsService ) {
			return $this->pm_promotions_service;
		}

		try {
			$service = function_exists( 'wc_get_container' ) ? wc_get_container()->get( WooPaymentsPmPromotionsService::class ) : null;
		} catch ( Throwable $e ) {
			$service = new WooPaymentsPmPromotionsService();
			$service->init( $this->api_client, $this->account_service );
		}

		if ( ! $service instanceof WooPaymentsPmPromotionsService ) {
			$service = new WooPaymentsPmPromotionsService();
			$service->init( $this->api_client, $this->account_service );
		}

		$this->pm_promotions_service = $service;

		return $this->pm_promotions_service;
	}

	/**
	 * Update native WooPayments settings.
	 *
	 * @param array<string,mixed> $params Request parameters.
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_settings( array $params ) {
		$settings                     = $this->get_gateway_settings();
		$available_payment_method_ids = $this->get_available_payment_method_ids( $settings );
		$was_woopay_enabled           = $this->is_yes( $settings['platform_checkout'] ?? 'no' );
		$error                        = $this->update_provider_backed_settings( $params, $settings );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		foreach ( self::LOCAL_SETTING_MAP as $request_key => $mapping ) {
			if ( ! array_key_exists( $request_key, $params ) ) {
				continue;
			}

			if ( $this->should_skip_local_setting_update_in_dev_mode( $request_key ) ) {
				continue;
			}

			list( $setting_key, $type ) = $mapping;
			$settings[ $setting_key ]   = $this->normalize_setting_value( $params[ $request_key ], $type );
		}

		if ( array_key_exists( 'is_woopay_enabled', $params ) && $was_woopay_enabled && ! $this->is_yes( $settings['platform_checkout'] ?? 'no' ) ) {
			$settings['platform_checkout_last_disable_date'] = gmdate( 'Y-m-d' );
		}

		if ( array_key_exists( 'woopay_custom_message', $params ) ) {
			$custom_message                               = is_scalar( $params['woopay_custom_message'] ) ? (string) $params['woopay_custom_message'] : '';
			$custom_message                               = str_replace( '[terms_of_service_link]', '[terms]', $custom_message );
			$custom_message                               = str_replace( '[privacy_policy_link]', '[privacy_policy]', $custom_message );
			$settings['platform_checkout_custom_message'] = $custom_message;
		}

		if ( array_key_exists( 'enabled_payment_method_ids', $params ) ) {
			$previous_enabled_payment_method_ids = $this->sanitize_payment_method_ids(
				$this->get_array_setting( $settings, 'upe_enabled_payment_method_ids', array( 'card' ) ),
				$available_payment_method_ids
			);
			$enabled_payment_method_ids          = $this->sanitize_payment_method_ids(
				is_array( $params['enabled_payment_method_ids'] ) ? $params['enabled_payment_method_ids'] : array(),
				$available_payment_method_ids
			);
			if ( $this->is_manual_capture_enabled_after_update( $params, $settings ) ) {
				$enabled_payment_method_ids = $this->filter_manual_capture_payment_method_ids( $enabled_payment_method_ids );
			}
			foreach ( array_diff( $enabled_payment_method_ids, $previous_enabled_payment_method_ids ) as $payment_method_id ) {
				$this->get_pm_promotions_service()->maybe_activate_promotion_for_payment_method( $payment_method_id );
			}
			$capability_error = $this->request_unrequested_payment_methods( $enabled_payment_method_ids );
			if ( is_wp_error( $capability_error ) ) {
				return $capability_error;
			}
			$settings['upe_enabled_payment_method_ids'] = $enabled_payment_method_ids;
		}

		foreach ( array( 'product', 'cart', 'checkout' ) as $location ) {
			$request_key = 'express_checkout_' . $location . '_methods';
			if ( array_key_exists( $request_key, $params ) ) {
				$settings[ $request_key ] = $this->sanitize_payment_method_ids(
					is_array( $params[ $request_key ] ) ? $params[ $request_key ] : array(),
					self::EXPRESS_CHECKOUT_METHOD_IDS
				);
			}
		}

		if ( array_key_exists( 'is_multi_currency_enabled', $params ) ) {
			update_option( self::MULTI_CURRENCY_FLAG_OPTION, $params['is_multi_currency_enabled'] ? '1' : '0' );
		}

		if ( array_key_exists( 'is_wcpay_subscriptions_enabled', $params ) && ! $params['is_wcpay_subscriptions_enabled'] ) {
			update_option( self::WCPAY_SUBSCRIPTIONS_FLAG_OPTION, '0' );
		}

		foreach ( array_keys( self::ACCOUNT_SETTING_MAP ) as $request_key ) {
			if ( array_key_exists( $request_key, $params ) ) {
				$settings[ $request_key ] = $params[ $request_key ];
			}
		}

		update_option( self::SETTINGS_OPTION, $settings );
		/**
		 * Fires after native WooPayments settings are updated so operational mirrors can sync setup state.
		 *
		 * @since 11.0.0
		 */
		do_action( WooPaymentsOperationalQueueService::STORE_SETUP_SYNC_ACTION );

		return $this->get_settings();
	}

	/**
	 * Update an allowlisted WordPress option used by the settings page.
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $value       Option value.
	 * @return true|WP_Error
	 */
	public function update_option( string $option_name, $value ) {
		$expected_type = self::ALLOWED_OPTIONS[ $option_name ] ?? null;
		if ( null === $expected_type ) {
			return new WP_Error(
				'woocommerce_woopayments_invalid_settings_option',
				esc_html__( 'Invalid WooPayments settings option.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $this->is_valid_option_value_type( $value, $expected_type ) ) {
			return new WP_Error(
				'woocommerce_woopayments_invalid_settings_option_value',
				sprintf(
					/* translators: %s: expected option value type. */
					esc_html__( 'Invalid WooPayments settings option value. Expected %s.', 'woocommerce' ),
					$expected_type
				),
				array( 'status' => 400 )
			);
		}

		update_option( $option_name, $value );

		return true;
	}

	/**
	 * Upload a file through the native API client.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>|WP_Error
	 */
	public function upload_file( WP_REST_Request $request ) {
		try {
			return $this->api_client->upload_file( $request );
		} catch ( WooPaymentsApiException $e ) {
			return $this->api_exception_to_wp_error( $e );
		}
	}

	/**
	 * Get provider file details.
	 *
	 * @param string $file_id    Provider file ID.
	 * @param bool   $as_account Whether to fetch the file as the connected account.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_file( string $file_id, bool $as_account = false ) {
		try {
			return $this->api_client->get_file( $file_id, $as_account );
		} catch ( WooPaymentsApiException $e ) {
			return $this->api_exception_to_wp_error( $e );
		}
	}

	/**
	 * Get provider file contents.
	 *
	 * @param string $file_id    Provider file ID.
	 * @param bool   $as_account Whether to fetch the file as the connected account.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_file_contents( string $file_id, bool $as_account = false ) {
		try {
			return $this->api_client->get_file_contents( $file_id, $as_account );
		} catch ( WooPaymentsApiException $e ) {
			return $this->api_exception_to_wp_error( $e );
		}
	}

	/**
	 * Get gateway settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_gateway_settings(): array {
		$settings = get_option( self::SETTINGS_OPTION, array() );

		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get payment method IDs available to the connected account.
	 *
	 * @param array<string,mixed> $settings Gateway settings.
	 * @return string[]
	 */
	private function get_available_payment_method_ids( array $settings ): array {
		$configured_available_ids = $settings['upe_available_payment_methods'] ?? null;
		if ( is_array( $configured_available_ids ) && ! empty( $configured_available_ids ) ) {
			return $this->sanitize_payment_method_ids( $configured_available_ids, self::SUPPORTED_PAYMENT_METHOD_IDS );
		}

		$account_data = $this->account_service->get_cached_account_data();
		$fees         = is_array( $account_data['fees'] ?? null ) ? array_keys( $account_data['fees'] ) : array();
		if ( ! empty( $fees ) ) {
			$available_ids = $this->sanitize_payment_method_ids( $fees, self::SUPPORTED_PAYMENT_METHOD_IDS );
			if ( in_array( 'card', $available_ids, true ) ) {
				$available_ids[] = 'apple_pay';
				$available_ids[] = 'google_pay';
			}

			return array_values( array_unique( $available_ids ) );
		}

		$enabled_ids = $this->get_array_setting( $settings, 'upe_enabled_payment_method_ids', array( 'card' ) );

		return $this->sanitize_payment_method_ids( array_merge( array( 'card' ), $enabled_ids ), self::SUPPORTED_PAYMENT_METHOD_IDS );
	}

	/**
	 * Get account fee structures from cached account data.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_account_fees(): array {
		$account_data = $this->account_service->get_cached_account_data();
		$fees         = is_array( $account_data['fees'] ?? null ) ? $account_data['fees'] : array();
		$account_fees = array();

		foreach ( $fees as $payment_method_id => $fee_structure ) {
			if ( ! is_string( $payment_method_id ) || ! in_array( $payment_method_id, self::SUPPORTED_PAYMENT_METHOD_IDS, true ) || ! is_array( $fee_structure ) ) {
				continue;
			}

			$account_fees[ $payment_method_id ] = $fee_structure;
		}

		return $account_fees;
	}

	/**
	 * Get dismissed duplicate payment method notice state.
	 *
	 * @return array<string,string[]>
	 */
	private function get_dismissed_duplicate_payment_method_notices(): array {
		$dismissed_notices = get_option( 'wcpay_duplicate_payment_method_notices_dismissed', array() );

		return $this->sanitize_duplicate_gateway_map( is_array( $dismissed_notices ) ? $dismissed_notices : array(), false );
	}

	/**
	 * Get payment methods enabled by WooPayments and another gateway.
	 *
	 * @return array<string,string[]>
	 */
	private function get_duplicated_payment_method_ids(): array {
		try {
			$duplicate_candidates = array();
			$settings             = $this->get_gateway_settings();

			foreach ( $this->get_registered_payment_gateways() as $gateway ) {
				if ( ! $this->is_gateway_enabled( $gateway ) ) {
					continue;
				}

				$gateway_id = $this->get_gateway_id( $gateway );
				if ( '' === $gateway_id ) {
					continue;
				}

				foreach ( $this->get_declared_duplicate_payment_method_ids_for_gateway( $gateway, $gateway_id ) as $payment_method_id ) {
					$duplicate_candidates[ $payment_method_id ][] = $gateway_id;
				}

				if ( $this->is_payment_request_duplicate_gateway( $gateway, $gateway_id, $settings ) ) {
					$duplicate_candidates[ self::PAYMENT_REQUEST_DUPLICATE_METHOD_ID ][] = $gateway_id;
				}

				$payment_method_id = $this->get_duplicate_payment_method_id_for_gateway( $gateway_id );
				if ( '' !== $payment_method_id ) {
					$duplicate_candidates[ $payment_method_id ][] = $gateway_id;
				}
			}

			return $this->keep_woopayments_duplicate_clusters_only( $duplicate_candidates );
		} catch ( Throwable $e ) {
			wc_get_logger()->warning(
				'Native WooPayments duplicate payment method detection failed: ' . $e->getMessage(),
				array( 'source' => 'woocommerce-woopayments-settings' )
			);

			return array();
		}
	}

	/**
	 * Keep only duplicate clusters that include WooPayments.
	 *
	 * @param array<string,string[]> $duplicate_candidates Duplicate candidates.
	 * @return array<string,string[]>
	 */
	private function keep_woopayments_duplicate_clusters_only( array $duplicate_candidates ): array {
		$duplicates = array();

		foreach ( $duplicate_candidates as $payment_method_id => $gateway_ids ) {
			$gateway_ids = array_values( array_unique( $gateway_ids ) );
			if ( count( $gateway_ids ) < 2 ) {
				continue;
			}

			$has_woopayments_gateway = array_filter(
				$gateway_ids,
				fn( string $gateway_id ): bool => $this->is_woopayments_gateway_id( $gateway_id )
			);
			if ( empty( $has_woopayments_gateway ) ) {
				continue;
			}

			$duplicates[ $payment_method_id ] = $gateway_ids;
		}

		return $duplicates;
	}

	/**
	 * Get payment method duplicate declarations for a gateway.
	 *
	 * @param object $gateway    Payment gateway.
	 * @param string $gateway_id Gateway ID.
	 * @return string[]
	 */
	private function get_declared_duplicate_payment_method_ids_for_gateway( object $gateway, string $gateway_id ): array {
		/**
		 * Filters native WooPayments payment method IDs duplicated by a payment gateway.
		 *
		 * This gives gateway integrations a precise declaration path without relying on gateway ID
		 * keywords or private option names. Return native method IDs such as "card" or the
		 * synthetic express-wallet ID "apple_pay_google_pay".
		 *
		 * @since 11.0.0
		 *
		 * @param string[] $payment_method_ids Native WooPayments payment method IDs duplicated by the gateway.
		 * @param string   $gateway_id         Payment gateway ID.
		 * @param object   $gateway            Payment gateway instance.
		 */
		$payment_method_ids = apply_filters(
			self::FILTER_GATEWAY_DUPLICATE_PAYMENT_METHOD_IDS,
			array(),
			$gateway_id,
			$gateway
		);

		if ( ! is_array( $payment_method_ids ) ) {
			return array();
		}

		return $this->sanitize_duplicate_payment_method_ids( $payment_method_ids );
	}

	/**
	 * Get registered WooCommerce payment gateways.
	 *
	 * @return object[]
	 */
	private function get_registered_payment_gateways(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return array();
		}

		$gateways = WC()->payment_gateways()->payment_gateways();

		return is_array( $gateways ) ? array_values( $gateways ) : array();
	}

	/**
	 * Get a gateway ID.
	 *
	 * @param object $gateway Payment gateway.
	 * @return string
	 */
	private function get_gateway_id( object $gateway ): string {
		return isset( $gateway->id ) && is_scalar( $gateway->id ) ? (string) $gateway->id : '';
	}

	/**
	 * Tell whether a gateway is enabled.
	 *
	 * @param object $gateway Payment gateway.
	 * @return bool
	 */
	private function is_gateway_enabled( object $gateway ): bool {
		return isset( $gateway->enabled ) && 'yes' === (string) $gateway->enabled;
	}

	/**
	 * Tell whether a gateway ID belongs to WooPayments.
	 *
	 * @param string $gateway_id Gateway ID.
	 * @return bool
	 */
	private function is_woopayments_gateway_id( string $gateway_id ): bool {
		return OrderPaymentStore::GATEWAY_ID === $gateway_id || 0 === strpos( $gateway_id, OrderPaymentStore::GATEWAY_ID_PREFIX );
	}

	/**
	 * Get the duplicate-detection payment method ID for a gateway ID.
	 *
	 * @param string $gateway_id Gateway ID.
	 * @return string
	 */
	private function get_duplicate_payment_method_id_for_gateway( string $gateway_id ): string {
		if ( OrderPaymentStore::GATEWAY_ID === $gateway_id ) {
			return 'card';
		}

		if ( 0 === strpos( $gateway_id, OrderPaymentStore::GATEWAY_ID_PREFIX ) ) {
			$payment_method_id = substr( $gateway_id, strlen( OrderPaymentStore::GATEWAY_ID_PREFIX ) );

			return in_array( $payment_method_id, self::SUPPORTED_PAYMENT_METHOD_IDS, true ) ? $payment_method_id : '';
		}

		foreach ( self::DUPLICATE_PAYMENT_METHOD_KEYWORDS as $payment_method_id => $keywords ) {
			if ( $this->gateway_id_contains_keyword( $gateway_id, $keywords ) ) {
				return $payment_method_id;
			}
		}

		return '';
	}

	/**
	 * Tell whether a gateway participates in the Apple Pay / Google Pay duplicate cluster.
	 *
	 * @param object              $gateway    Payment gateway.
	 * @param string              $gateway_id Gateway ID.
	 * @param array<string,mixed> $settings   Gateway settings.
	 * @return bool
	 */
	private function is_payment_request_duplicate_gateway( object $gateway, string $gateway_id, array $settings ): bool {
		if ( OrderPaymentStore::GATEWAY_ID === $gateway_id ) {
			return $this->is_yes( $settings['payment_request'] ?? 'yes' );
		}

		if ( $this->is_woopayments_gateway_id( $gateway_id ) ) {
			return false;
		}

		if ( $this->gateway_id_contains_keyword( $gateway_id, self::PAYMENT_REQUEST_DUPLICATE_GATEWAY_KEYWORDS ) ) {
			return true;
		}

		if ( 'stripe' === $gateway_id && $this->is_gateway_option_enabled( $gateway, 'payment_request' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Tell whether a gateway option is enabled.
	 *
	 * @param object $gateway Payment gateway.
	 * @param string $key     Option key.
	 * @return bool
	 */
	private function is_gateway_option_enabled( object $gateway, string $key ): bool {
		if ( ! method_exists( $gateway, 'get_option' ) ) {
			return false;
		}

		$value = $gateway->get_option( $key );

		return is_scalar( $value ) && $this->is_yes( $value );
	}

	/**
	 * Tell whether a gateway ID contains one of the given keywords.
	 *
	 * @param string   $gateway_id Gateway ID.
	 * @param string[] $keywords   Keywords.
	 * @return bool
	 */
	private function gateway_id_contains_keyword( string $gateway_id, array $keywords ): bool {
		foreach ( $keywords as $keyword ) {
			if ( false !== strpos( $gateway_id, $keyword ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sanitize duplicate-detection payment method IDs.
	 *
	 * @param mixed[] $payment_method_ids Payment method IDs.
	 * @return string[]
	 */
	private function sanitize_duplicate_payment_method_ids( array $payment_method_ids ): array {
		$allowed_payment_method_ids = array_merge(
			self::SUPPORTED_PAYMENT_METHOD_IDS,
			array( self::PAYMENT_REQUEST_DUPLICATE_METHOD_ID )
		);

		return array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( $payment_method_id ): string => is_scalar( $payment_method_id ) ? (string) $payment_method_id : '',
						$payment_method_ids
					),
					static fn( string $payment_method_id ): bool => in_array( $payment_method_id, $allowed_payment_method_ids, true )
				)
			)
		);
	}

	/**
	 * Sanitize a payment-method to gateway IDs map.
	 *
	 * @param array<int|string,mixed> $gateway_map              Raw gateway map.
	 * @param bool                    $supported_methods_only  Whether to remove unsupported payment method IDs.
	 * @return array<string,string[]>
	 */
	private function sanitize_duplicate_gateway_map( array $gateway_map, bool $supported_methods_only = true ): array {
		$sanitized = array();

		foreach ( $gateway_map as $payment_method_id => $gateway_ids ) {
			if ( ! is_string( $payment_method_id ) || ! is_array( $gateway_ids ) ) {
				continue;
			}

			if ( $supported_methods_only && ! in_array( $payment_method_id, self::SUPPORTED_PAYMENT_METHOD_IDS, true ) ) {
				continue;
			}

			$gateway_ids = array_values(
				array_unique(
					array_filter(
						array_map(
							static fn( $gateway_id ): string => is_scalar( $gateway_id ) ? (string) $gateway_id : '',
							$gateway_ids
						)
					)
				)
			);

			if ( empty( $gateway_ids ) ) {
				continue;
			}

			$sanitized[ $payment_method_id ] = $gateway_ids;
		}

		return $sanitized;
	}

	/**
	 * Tell whether manual capture will be enabled after this update.
	 *
	 * @param array<string,mixed> $params   Request parameters.
	 * @param array<string,mixed> $settings Current gateway settings.
	 * @return bool
	 */
	private function is_manual_capture_enabled_after_update( array $params, array $settings ): bool {
		if ( array_key_exists( 'is_manual_capture_enabled', $params ) ) {
			return (bool) $params['is_manual_capture_enabled'];
		}

		return $this->is_yes( $settings['manual_capture'] ?? 'no' );
	}

	/**
	 * Filter payment methods to those compatible with manual capture.
	 *
	 * @param string[] $payment_method_ids Payment method IDs.
	 * @return string[]
	 */
	private function filter_manual_capture_payment_method_ids( array $payment_method_ids ): array {
		return array_values(
			array_filter(
				$payment_method_ids,
				static fn( string $payment_method_id ): bool => in_array( $payment_method_id, self::MANUAL_CAPTURE_PAYMENT_METHOD_IDS, true )
			)
		);
	}

	/**
	 * Update provider-backed account and fraud settings.
	 *
	 * @param array<string,mixed> $params   Request parameters.
	 * @param array<string,mixed> $settings Current gateway settings.
	 * @return WP_Error|null
	 */
	private function update_provider_backed_settings( array $params, array $settings ): ?WP_Error {
		$account_response_fields = $this->get_account_backed_response_fields( $settings );
		$account_settings        = array();
		foreach ( self::ACCOUNT_SETTING_MAP as $request_key => $account_key ) {
			if ( array_key_exists( $request_key, $params ) && ( $account_response_fields[ $request_key ] ?? null ) !== $params[ $request_key ] ) {
				$account_settings[ $account_key ] = $params[ $request_key ];
			}
		}

		if (
			! isset( $account_settings['deposit_schedule_interval'] )
			&& (
				isset( $account_settings['deposit_schedule_weekly_anchor'] )
				|| isset( $account_settings['deposit_schedule_monthly_anchor'] )
			)
		) {
			$account_settings['deposit_schedule_interval'] = array_key_exists( 'deposit_schedule_interval', $params )
				? $params['deposit_schedule_interval']
				: ( $settings['deposit_schedule_interval'] ?? '' );
		}

		try {
			if ( ! empty( $account_settings ) ) {
				$this->api_client->update_account( $account_settings );
				$this->account_service->refresh_account_data();
			}

			$fraud_settings = $this->get_changed_fraud_settings( $params, $settings );
			if ( null !== $fraud_settings ) {
				$this->api_client->save_fraud_ruleset( $fraud_settings['ruleset_config'] );
				set_transient( 'wcpay_fraud_protection_settings', $fraud_settings['ruleset_config'], DAY_IN_SECONDS );
				update_option( 'current_protection_level', $fraud_settings['protection_level'] );
				$this->sync_cached_fraud_mitigation_settings_after_fraud_save( $fraud_settings );
			}
		} catch ( WooPaymentsApiException $e ) {
			return $this->api_exception_to_wp_error( $e );
		}

		return null;
	}

	/**
	 * Get changed fraud settings from request parameters.
	 *
	 * @param array<string,mixed> $params   Request parameters.
	 * @param array<string,mixed> $settings Current gateway settings.
	 * @return array{protection_level:string,ruleset_config:array<int|string,mixed>}|null
	 */
	private function get_changed_fraud_settings( array $params, array $settings ): ?array {
		if ( ! array_key_exists( 'current_protection_level', $params ) || ! array_key_exists( 'advanced_fraud_protection_settings', $params ) ) {
			return null;
		}

		$protection_level = is_scalar( $params['current_protection_level'] ) ? (string) $params['current_protection_level'] : '';
		if ( ! in_array( $protection_level, array( 'basic', 'standard', 'high', 'advanced' ), true ) ) {
			return null;
		}

		$ruleset_config = $this->get_fraud_ruleset_for_protection_level( $protection_level, $params['advanced_fraud_protection_settings'] ?? array() );
		if ( null === $ruleset_config ) {
			return null;
		}
		$current_level = $this->get_current_protection_level();
		$current_rules = $this->get_advanced_fraud_protection_settings( $settings );

		if ( $current_level === $protection_level && $current_rules === $ruleset_config ) {
			return null;
		}

		return array(
			'protection_level' => $protection_level,
			'ruleset_config'   => $ruleset_config,
		);
	}

	/**
	 * Keep account-level fraud mitigation flags aligned with the just-saved advanced ruleset.
	 *
	 * @param array{protection_level:string,ruleset_config:array<int|string,mixed>} $fraud_settings Saved fraud settings.
	 * @return void
	 */
	private function sync_cached_fraud_mitigation_settings_after_fraud_save( array $fraud_settings ): void {
		if ( 'advanced' !== $fraud_settings['protection_level'] ) {
			return;
		}

		$account_data = $this->account_service->get_cached_account_data();
		if ( empty( $account_data ) ) {
			return;
		}

		$fraud_mitigation_settings                      = is_array( $account_data['fraud_mitigation_settings'] ?? null ) ? $account_data['fraud_mitigation_settings'] : array();
		$fraud_mitigation_settings['avs_check_enabled'] = $this->ruleset_contains_fraud_rule( $fraud_settings['ruleset_config'], 'avs_verification' );
		$account_data['fraud_mitigation_settings']      = $fraud_mitigation_settings;

		$this->account_service->cache_account_data( $account_data );
	}

	/**
	 * Check whether a fraud ruleset contains a rule key.
	 *
	 * @param array<int|string,mixed> $ruleset  Fraud ruleset.
	 * @param string                  $rule_key Rule key.
	 * @return bool
	 */
	private function ruleset_contains_fraud_rule( array $ruleset, string $rule_key ): bool {
		foreach ( $ruleset as $rule ) {
			if ( is_array( $rule ) && ( $rule['key'] ?? null ) === $rule_key ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the fraud ruleset for a protection level.
	 *
	 * @param string $protection_level Protection level.
	 * @param mixed  $advanced_ruleset Submitted advanced ruleset.
	 * @return array<int,array<string,mixed>>|null
	 */
	private function get_fraud_ruleset_for_protection_level( string $protection_level, $advanced_ruleset ): ?array {
		switch ( $protection_level ) {
			case 'basic':
				return array();
			case 'standard':
				return $this->get_standard_fraud_ruleset();
			case 'high':
				return $this->get_high_fraud_ruleset();
			case 'advanced':
				if ( ! is_array( $advanced_ruleset ) || ! $this->is_valid_fraud_ruleset( $advanced_ruleset ) ) {
					return null;
				}

				return $advanced_ruleset;
		}

		return null;
	}

	/**
	 * Get the standard fraud protection ruleset.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_standard_fraud_ruleset(): array {
		return array(
			$this->get_international_ip_address_rule( 'block' ),
			$this->get_rule( 'order_items_threshold', 'block', $this->get_check( 'item_count', 'greater_than', 10 ) ),
			$this->get_rule( 'purchase_price_threshold', 'block', $this->get_check( 'order_total', 'greater_than', $this->get_fraud_threshold_amount() ) ),
			$this->get_rule( 'ip_address_mismatch', 'block', $this->get_check( 'ip_billing_country_same', 'equals', false ) ),
		);
	}

	/**
	 * Get the high fraud protection ruleset.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_high_fraud_ruleset(): array {
		return array(
			$this->get_international_ip_address_rule( 'block' ),
			$this->get_rule( 'purchase_price_threshold', 'block', $this->get_check( 'order_total', 'greater_than', $this->get_fraud_threshold_amount() ) ),
			$this->get_rule(
				'order_items_threshold',
				'block',
				array(
					'operator' => 'or',
					'checks'   => array(
						$this->get_check( 'item_count', 'less_than', 2 ),
						$this->get_check( 'item_count', 'greater_than', 10 ),
					),
				)
			),
			$this->get_rule( 'address_mismatch', 'block', $this->get_check( 'billing_shipping_address_same', 'equals', false ) ),
			$this->get_rule( 'ip_address_mismatch', 'block', $this->get_check( 'ip_billing_country_same', 'equals', false ) ),
		);
	}

	/**
	 * Get the international IP address rule.
	 *
	 * @param string $outcome Rule outcome.
	 * @return array<string,mixed>
	 */
	private function get_international_ip_address_rule( string $outcome ): array {
		return $this->get_rule(
			'international_ip_address',
			$outcome,
			$this->get_check( 'ip_country', $this->get_selling_locations_type_operator(), $this->get_selling_locations_string() )
		);
	}

	/**
	 * Get a fraud protection rule array.
	 *
	 * @param string              $key     Rule key.
	 * @param string              $outcome Rule outcome.
	 * @param array<string,mixed> $check   Rule check.
	 * @return array<string,mixed>
	 */
	private function get_rule( string $key, string $outcome, array $check ): array {
		return array(
			'key'     => $key,
			'outcome' => $outcome,
			'check'   => $check,
		);
	}

	/**
	 * Get a fraud protection check array.
	 *
	 * @param string $key      Check key.
	 * @param string $operator Check operator.
	 * @param mixed  $value    Check value.
	 * @return array<string,mixed>
	 */
	private function get_check( string $key, string $operator, $value ): array {
		return array(
			'key'      => $key,
			'operator' => $operator,
			'value'    => $value,
		);
	}

	/**
	 * Validate a submitted fraud ruleset.
	 *
	 * @param array<int|string,mixed> $ruleset Submitted ruleset.
	 * @return bool
	 */
	private function is_valid_fraud_ruleset( array $ruleset ): bool {
		foreach ( $ruleset as $rule ) {
			if ( ! is_array( $rule ) || ! $this->is_valid_fraud_rule( $rule ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate a submitted fraud rule.
	 *
	 * @param array<int|string,mixed> $rule Submitted rule.
	 * @return bool
	 */
	private function is_valid_fraud_rule( array $rule ): bool {
		return isset( $rule['key'], $rule['outcome'], $rule['check'] )
			&& is_string( $rule['key'] )
			&& in_array( $rule['outcome'], array( 'allow', 'block', 'review' ), true )
			&& is_array( $rule['check'] )
			&& $this->is_valid_fraud_check( $rule['check'] );
	}

	/**
	 * Validate a submitted fraud check.
	 *
	 * @param array<int|string,mixed> $check Submitted check.
	 * @return bool
	 */
	private function is_valid_fraud_check( array $check ): bool {
		if ( isset( $check['operator'] ) && in_array( $check['operator'], array( 'and', 'or' ), true ) ) {
			if ( empty( $check['checks'] ) || ! is_array( $check['checks'] ) ) {
				return false;
			}

			foreach ( $check['checks'] as $child_check ) {
				if ( ! is_array( $child_check ) || ! $this->is_valid_fraud_check( $child_check ) ) {
					return false;
				}
			}

			return true;
		}

		return isset( $check['key'], $check['operator'] )
			&& in_array( $check['operator'], array( 'equals', 'not_equals', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'in', 'not_in' ), true )
			&& array_key_exists( 'value', $check );
	}

	/**
	 * Get the international selling-locations operator.
	 *
	 * @return string
	 */
	private function get_selling_locations_type_operator(): string {
		return 'specific' === get_option( 'woocommerce_allowed_countries', 'all' ) ? 'not_in' : 'in';
	}

	/**
	 * Get the lower-case pipe-separated selling-locations list.
	 *
	 * @return string
	 */
	private function get_selling_locations_string(): string {
		$selling_locations_type = get_option( 'woocommerce_allowed_countries', 'all' );
		if ( 'specific' === $selling_locations_type ) {
			$countries = get_option( 'woocommerce_specific_allowed_countries', array() );
		} elseif ( 'all_except' === $selling_locations_type ) {
			$countries = get_option( 'woocommerce_all_except_countries', array() );
		} else {
			$countries = array();
		}

		return is_array( $countries ) ? strtolower( implode( '|', $countries ) ) : '';
	}

	/**
	 * Get the fraud threshold amount payload.
	 *
	 * @return string
	 */
	private function get_fraud_threshold_amount(): string {
		$default_currency = $this->account_service->get_account_default_currency();
		$currency         = strtolower( '' !== $default_currency ? $default_currency : 'usd' );

		return '100000|' . $currency;
	}

	/**
	 * Get payment method statuses.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_payment_method_statuses(): array {
		$account_data = $this->account_service->get_cached_account_data();
		$capabilities = is_array( $account_data['capabilities'] ?? null ) ? $account_data['capabilities'] : array();
		$requirements = is_array( $account_data['capability_requirements'] ?? null ) ? $account_data['capability_requirements'] : array();
		$statuses     = array();

		foreach ( $capabilities as $capability_id => $status ) {
			if ( ! is_scalar( $status ) ) {
				continue;
			}

			$capability_id              = (string) $capability_id;
			$statuses[ $capability_id ] = array(
				'status'       => (string) $status,
				'requirements' => is_array( $requirements[ $capability_id ] ?? null ) ? $requirements[ $capability_id ] : array(),
			);
		}

		if ( ! empty( $statuses ) ) {
			return $statuses;
		}

		return array(
			'card_payments' => array(
				'status'       => 'active',
				'requirements' => array(),
			),
		);
	}

	/**
	 * Request any unrequested capabilities for newly enabled methods.
	 *
	 * @param string[] $payment_method_ids Enabled payment method IDs.
	 * @return WP_Error|null
	 */
	private function request_unrequested_payment_methods( array $payment_method_ids ): ?WP_Error {
		$payment_method_statuses = $this->get_payment_method_statuses();
		$cache_needs_refresh     = false;

		try {
			foreach ( $payment_method_ids as $payment_method_id ) {
				$capability_id = self::PAYMENT_METHOD_CAPABILITY_KEY_MAP[ $payment_method_id ] ?? null;
				if ( null === $capability_id || 'unrequested' !== ( $payment_method_statuses[ $capability_id ]['status'] ?? null ) ) {
					continue;
				}

				$request_result      = $this->api_client->request_capability( $capability_id, true );
				$cache_needs_refresh = $cache_needs_refresh || 'unrequested' !== ( $request_result['status'] ?? 'unrequested' );
			}

			if ( $cache_needs_refresh ) {
				$this->account_service->refresh_account_data();
			}
		} catch ( WooPaymentsApiException $e ) {
			return $this->api_exception_to_wp_error( $e );
		}

		return null;
	}

	/**
	 * Get current fraud protection level.
	 *
	 * @return string
	 */
	private function get_current_protection_level(): string {
		$level = get_option( 'current_protection_level', 'basic' );

		return is_scalar( $level ) ? (string) $level : 'basic';
	}

	/**
	 * Get advanced fraud protection settings.
	 *
	 * @param array<string,mixed> $settings Current gateway settings.
	 * @return array<int|string,mixed>
	 */
	private function get_advanced_fraud_protection_settings( array $settings ): array {
		$ruleset = get_transient( 'wcpay_fraud_protection_settings' );
		if ( is_array( $ruleset ) ) {
			return $ruleset;
		}

		return $this->get_array_setting( $settings, 'advanced_fraud_protection_settings' );
	}

	/**
	 * Get the WooCommerce store currency for fraud threshold rules.
	 *
	 * @return string
	 */
	private function get_store_currency(): string {
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'woocommerce_currency', 'USD' );

		return is_scalar( $currency ) ? strtoupper( (string) $currency ) : 'USD';
	}

	/**
	 * Get platform fraud protection settings projected from cached account data.
	 *
	 * @return array<string,bool>
	 */
	private function get_fraud_protection_settings(): array {
		$account_data = $this->account_service->get_cached_account_data();

		return array(
			'decline_on_avs_failure' => $this->get_account_bool_setting( $account_data, array( 'fraud_mitigation_settings', 'avs_check_enabled' ), true ),
			'decline_on_cvc_failure' => $this->get_account_bool_setting( $account_data, array( 'fraud_mitigation_settings', 'cvc_check_enabled' ), true ),
		);
	}

	/**
	 * Get the WooCommerce selling-location settings used by fraud filters.
	 *
	 * @return array{type:string,countries:string[]}
	 */
	private function get_fraud_protection_allowed_countries(): array {
		$selling_locations_type = get_option( 'woocommerce_allowed_countries', 'all' );
		$selling_locations_type = is_scalar( $selling_locations_type ) ? (string) $selling_locations_type : 'all';

		if ( 'specific' === $selling_locations_type ) {
			$countries = get_option( 'woocommerce_specific_allowed_countries', array() );
		} elseif ( 'all_except' === $selling_locations_type ) {
			$countries = get_option( 'woocommerce_all_except_countries', array() );
		} else {
			$countries = array();
		}

		return array(
			'type'      => in_array( $selling_locations_type, array( 'all', 'specific', 'all_except' ), true ) ? $selling_locations_type : 'all',
			'countries' => is_array( $countries ) ? array_values( array_filter( $countries, 'is_string' ) ) : array(),
		);
	}

	/**
	 * Tell whether fraud-protection review outcomes are enabled.
	 *
	 * @return bool
	 */
	private function is_fraud_protection_review_feature_active(): bool {
		return '1' === (string) get_option( 'wcpay_frt_review_feature_active', '0' );
	}

	/**
	 * Convert native API exceptions into REST-safe errors.
	 *
	 * @param WooPaymentsApiException $exception API exception.
	 * @return WP_Error
	 */
	private function api_exception_to_wp_error( WooPaymentsApiException $exception ): WP_Error {
		return new WP_Error(
			$exception->get_error_code(),
			$exception->getMessage(),
			array( 'status' => $exception->get_http_code() )
		);
	}

	/**
	 * Normalize a local gateway setting value.
	 *
	 * @param mixed  $value Value.
	 * @param string $type  Setting type.
	 * @return mixed
	 */
	private function normalize_setting_value( $value, string $type ) {
		if ( 'bool' === $type ) {
			return $value ? 'yes' : 'no';
		}

		if ( 'int' === $type ) {
			return (int) $value;
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Tell whether an option value matches the expected type.
	 *
	 * @param mixed  $value         Option value.
	 * @param string $expected_type Expected type.
	 * @return bool
	 */
	private function is_valid_option_value_type( $value, string $expected_type ): bool {
		if ( 'bool' === $expected_type ) {
			return is_bool( $value );
		}

		if ( 'array' === $expected_type ) {
			return is_array( $value );
		}

		return 'string' === $expected_type && is_string( $value );
	}

	/**
	 * Tell whether Subscriptions should be treated as eligible.
	 *
	 * @return bool
	 */
	private function is_subscriptions_eligible(): bool {
		return class_exists( 'WC_Subscriptions' ) || class_exists( 'WC_Subscriptions_Core_Plugin' );
	}

	/**
	 * Normalize a yes/no value.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private function is_yes( $value ): bool {
		return 'yes' === $value || true === $value || '1' === $value || 1 === $value;
	}

	/**
	 * Get settings fields projected from cached account data.
	 *
	 * @param array<string,mixed> $settings Gateway settings.
	 * @return array<string,mixed>
	 */
	private function get_account_backed_response_fields( array $settings ): array {
		$account_data = $this->account_service->get_cached_account_data();

		return array(
			'account_country'                    => $this->get_account_string_setting( $account_data, array( 'country' ), $this->get_string_setting( $settings, 'account_country' ) ),
			'account_statement_descriptor'       => $this->get_account_string_setting( $account_data, array( 'statement_descriptor' ), $this->get_string_setting( $settings, 'account_statement_descriptor' ) ),
			'account_statement_descriptor_kanji' => $this->get_account_string_setting( $account_data, array( 'statement_descriptor_kanji' ), $this->get_string_setting( $settings, 'account_statement_descriptor_kanji' ) ),
			'account_statement_descriptor_kana'  => $this->get_account_string_setting( $account_data, array( 'statement_descriptor_kana' ), $this->get_string_setting( $settings, 'account_statement_descriptor_kana' ) ),
			'account_business_name'              => $this->get_account_string_setting( $account_data, array( 'business_profile', 'name' ), $this->get_string_setting( $settings, 'account_business_name' ) ),
			'account_business_url'               => $this->get_account_string_setting( $account_data, array( 'business_profile', 'url' ), $this->get_string_setting( $settings, 'account_business_url' ) ),
			'account_business_support_address'   => $this->get_account_array_setting( $account_data, array( 'business_profile', 'support_address' ), $this->get_array_setting( $settings, 'account_business_support_address' ) ),
			'account_business_support_email'     => $this->get_account_string_setting( $account_data, array( 'business_profile', 'support_email' ), $this->get_string_setting( $settings, 'account_business_support_email' ) ),
			'account_business_support_phone'     => $this->get_account_string_setting( $account_data, array( 'business_profile', 'support_phone' ), $this->get_string_setting( $settings, 'account_business_support_phone' ) ),
			'account_branding_logo'              => $this->get_account_string_setting( $account_data, array( 'branding', 'logo' ), $this->get_string_setting( $settings, 'account_branding_logo' ) ),
			'account_branding_icon'              => $this->get_account_string_setting( $account_data, array( 'branding', 'icon' ), $this->get_string_setting( $settings, 'account_branding_icon' ) ),
			'account_branding_primary_color'     => $this->get_account_string_setting( $account_data, array( 'branding', 'primary_color' ), $this->get_string_setting( $settings, 'account_branding_primary_color' ) ),
			'account_branding_secondary_color'   => $this->get_account_string_setting( $account_data, array( 'branding', 'secondary_color' ), $this->get_string_setting( $settings, 'account_branding_secondary_color' ) ),
			'account_domestic_currency'          => $this->get_string_setting( $settings, 'account_domestic_currency', $this->account_service->get_account_default_currency() ),
			'account_communications_email'       => $this->get_account_string_setting( $account_data, array( 'communications_email' ), $this->get_string_setting( $settings, 'account_communications_email' ) ),
			'deposit_schedule_interval'          => $this->get_account_string_setting( $account_data, array( 'deposits', 'interval' ), $this->get_string_setting( $settings, 'deposit_schedule_interval' ) ),
			'deposit_schedule_monthly_anchor'    => $this->get_account_nullable_int_setting( $account_data, array( 'deposits', 'monthly_anchor' ), $this->get_nullable_int_setting( $settings, 'deposit_schedule_monthly_anchor' ) ),
			'deposit_schedule_weekly_anchor'     => $this->get_account_string_setting( $account_data, array( 'deposits', 'weekly_anchor' ), $this->get_string_setting( $settings, 'deposit_schedule_weekly_anchor' ) ),
			'deposit_delay_days'                 => $this->get_account_nullable_int_setting( $account_data, array( 'deposits', 'delay_days' ), $this->get_nullable_int_setting( $settings, 'deposit_delay_days' ) ),
			'deposit_status'                     => $this->get_account_string_setting( $account_data, array( 'deposits', 'status' ), $this->get_string_setting( $settings, 'deposit_status' ) ),
			'deposit_restrictions'               => $this->get_account_string_setting( $account_data, array( 'deposits', 'restrictions' ), $this->get_string_setting( $settings, 'deposit_restrictions' ) ),
			'deposit_completed_waiting_period'   => $this->get_account_bool_setting( $account_data, array( 'deposits', 'completed_waiting_period' ), (bool) ( $settings['deposit_completed_waiting_period'] ?? false ) ),
		);
	}

	/**
	 * Get a nested scalar account value as a string.
	 *
	 * @param array<string,mixed> $account_data Account data.
	 * @param string[]            $path         Nested path.
	 * @param string              $fallback     Fallback value.
	 * @return string
	 */
	private function get_account_string_setting( array $account_data, array $path, string $fallback = '' ): string {
		$value = $this->get_account_path_value( $account_data, $path, $fallback );

		return is_scalar( $value ) ? (string) $value : $fallback;
	}

	/**
	 * Get a nested account value as an array.
	 *
	 * @param array<string,mixed>     $account_data Account data.
	 * @param string[]                $path         Nested path.
	 * @param array<int|string,mixed> $fallback     Fallback value.
	 * @return array<int|string,mixed>
	 */
	private function get_account_array_setting( array $account_data, array $path, array $fallback = array() ): array {
		$value = $this->get_account_path_value( $account_data, $path, $fallback );

		return is_array( $value ) ? $value : $fallback;
	}

	/**
	 * Get a nested account value as an integer or null.
	 *
	 * @param array<string,mixed> $account_data Account data.
	 * @param string[]            $path         Nested path.
	 * @param int|null            $fallback     Fallback value.
	 * @return int|null
	 */
	private function get_account_nullable_int_setting( array $account_data, array $path, ?int $fallback = null ): ?int {
		$value = $this->get_account_path_value( $account_data, $path, $fallback );

		return is_numeric( $value ) ? (int) $value : $fallback;
	}

	/**
	 * Get a nested account value as a boolean.
	 *
	 * @param array<string,mixed> $account_data Account data.
	 * @param string[]            $path         Nested path.
	 * @param bool                $fallback     Fallback value.
	 * @return bool
	 */
	private function get_account_bool_setting( array $account_data, array $path, bool $fallback = false ): bool {
		$value = $this->get_account_path_value( $account_data, $path, $fallback );

		return is_bool( $value ) ? $value : $this->is_yes( $value );
	}

	/**
	 * Get a nested value from account data.
	 *
	 * @param array<string,mixed> $account_data Account data.
	 * @param string[]            $path         Nested path.
	 * @param mixed               $fallback     Fallback value.
	 * @return mixed
	 */
	private function get_account_path_value( array $account_data, array $path, $fallback ) {
		$value = $account_data;
		foreach ( $path as $key ) {
			if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
				return $fallback;
			}
			$value = $value[ $key ];
		}

		return $value;
	}

	/**
	 * Get a scalar setting as a string.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $key      Setting key.
	 * @param string              $fallback Fallback value.
	 * @return string
	 */
	private function get_string_setting( array $settings, string $key, string $fallback = '' ): string {
		return isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? (string) $settings[ $key ] : $fallback;
	}

	/**
	 * Get an array setting.
	 *
	 * @param array<string,mixed>     $settings Settings.
	 * @param string                  $key      Setting key.
	 * @param array<int|string,mixed> $fallback Fallback value.
	 * @return array<int|string,mixed>
	 */
	private function get_array_setting( array $settings, string $key, array $fallback = array() ): array {
		return isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Get a nullable integer setting.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $key      Setting key.
	 * @return int|null
	 */
	private function get_nullable_int_setting( array $settings, string $key ): ?int {
		return isset( $settings[ $key ] ) && is_numeric( $settings[ $key ] ) ? (int) $settings[ $key ] : null;
	}

	/**
	 * Get an integer setting.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $key      Setting key.
	 * @param int                 $fallback Fallback value.
	 * @return int
	 */
	private function get_int_setting( array $settings, string $key, int $fallback = 0 ): int {
		return isset( $settings[ $key ] ) && is_numeric( $settings[ $key ] ) ? (int) $settings[ $key ] : $fallback;
	}

	/**
	 * Tell whether a local setting update should be ignored in dev mode.
	 *
	 * @param string $request_key Request key.
	 * @return bool
	 */
	private function should_skip_local_setting_update_in_dev_mode( string $request_key ): bool {
		return $this->account_service->is_dev_mode_enabled()
			&& in_array( $request_key, array( 'is_test_mode_enabled', 'is_debug_log_enabled' ), true );
	}

	/**
	 * Keep only supported payment method IDs.
	 *
	 * @param array<int|string,mixed> $payment_method_ids Raw payment method IDs.
	 * @param string[]                $allowed_ids        Allowed payment method IDs.
	 * @return string[]
	 */
	private function sanitize_payment_method_ids( array $payment_method_ids, array $allowed_ids ): array {
		return array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( $payment_method_id ): string => is_scalar( $payment_method_id ) ? (string) $payment_method_id : '',
						$payment_method_ids
					),
					static fn( string $payment_method_id ): bool => in_array( $payment_method_id, $allowed_ids, true )
				)
			)
		);
	}
}
