<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsSettingsService;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;
use WP_Error;
use WP_REST_Request;

/**
 * Tests for the WooPaymentsSettingsService class.
 */
class WooPaymentsSettingsServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsSettingsService
	 */
	private $sut;

	/**
	 * Recording native API client.
	 *
	 * @var RecordingSettingsApiClient
	 */
	private RecordingSettingsApiClient $api_client;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_client = new RecordingSettingsApiClient();
		$this->sut        = new WooPaymentsSettingsService();
		$this->sut->init( $this->create_account_service(), $this->api_client );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_woocommerce_payments_settings' );
		delete_option( 'wcpay_account_data' );
		delete_option( 'woopay_invalid_extension_found' );
		delete_option( '_wcpay_feature_customer_multi_currency' );
		delete_option( '_wcpay_feature_subscriptions' );
		delete_option( '_wcpay_feature_stripe_billing' );
		delete_option( 'wcpay_duplicate_payment_method_notices_dismissed' );
		delete_option( 'wcpay_fraud_protection_welcome_tour_dismissed' );
		delete_option( 'current_protection_level' );
		delete_transient( 'wcpay_fraud_protection_settings' );
		remove_all_filters( 'wcpay_dev_mode' );
		remove_all_filters( 'wcpay_test_mode' );
		remove_all_filters( 'wcpay_test_mode_onboarding' );

		parent::tearDown();
	}

	/**
	 * @testdox Should return the native WooPayments settings contract without Stripe Billing fields.
	 */
	public function test_get_settings_returns_reference_shaped_contract_without_stripe_billing_fields(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'enabled'                                => 'yes',
				'manual_capture'                         => 'yes',
				'test_mode'                              => 'yes',
				'enable_logging'                         => 'yes',
				'saved_cards'                            => 'no',
				'payment_request'                        => 'yes',
				'express_checkout_in_payment_methods'    => 'yes',
				'payment_request_button_size'            => 'large',
				'payment_request_button_type'            => 'buy',
				'payment_request_button_theme'           => 'dark',
				'payment_request_button_border_radius'   => 12,
				'platform_checkout'                      => 'yes',
				'is_woopay_global_theme_support_enabled' => 'yes',
				'platform_checkout_custom_message'       => 'Custom WooPay message.',
				'platform_checkout_store_logo'           => 'file_logo',
				'upe_enabled_payment_method_ids'         => array( 'card', 'link' ),
				'account_country'                        => 'US',
				'account_statement_descriptor'           => 'NATIVE STORE',
				'account_business_name'                  => 'Native Store',
				'account_business_url'                   => 'https://example.test',
				'account_business_support_address'       => array( 'city' => 'San Francisco' ),
				'account_business_support_email'         => 'support@example.test',
				'account_business_support_phone'         => '+15555550123',
				'account_branding_logo'                  => 'file_logo',
				'account_branding_icon'                  => 'file_icon',
				'account_branding_primary_color'         => '#111111',
				'account_branding_secondary_color'       => '#eeeeee',
				'account_domestic_currency'              => 'usd',
				'account_communications_email'           => 'owner@example.test',
				'deposit_schedule_interval'              => 'weekly',
				'deposit_schedule_weekly_anchor'         => 'monday',
				'deposit_schedule_monthly_anchor'        => 15,
				'deposit_delay_days'                     => 2,
				'deposit_status'                         => 'enabled',
				'deposit_restrictions'                   => array( 'minimum_balance' => 1000 ),
				'deposit_completed_waiting_period'       => true,
				'current_protection_level'               => 'advanced',
				'advanced_fraud_protection_settings'     => array( array( 'key' => 'avs_check' ) ),
				'express_checkout_product_methods'       => array( 'payment_request' ),
				'express_checkout_cart_methods'          => array( 'amazon_pay' ),
				'express_checkout_checkout_methods'      => array( 'woopay' ),
				'is_stripe_billing_enabled'              => true,
			)
		);
		update_option( '_wcpay_feature_customer_multi_currency', '1' );
		update_option( '_wcpay_feature_subscriptions', '1' );
		update_option( 'woopay_invalid_extension_found', true );
		update_option(
			'wcpay_account_data',
			array(
				'data'    => array(
					'account_id'              => 'acct_native_test',
					'is_live'                 => true,
					'capabilities'            => array(
						'card_payments' => 'active',
						'link_payments' => 'unrequested',
					),
					'capability_requirements' => array(
						'link_payments' => array( 'currently_due' => array( 'tos_acceptance.date' ) ),
					),
					'fees'                    => array(
						'card'   => array(),
						'link'   => array(),
						'affirm' => array(),
						'ideal'  => array(),
					),
					'store_currencies'        => array(
						'default'   => 'usd',
						'supported' => array( 'usd' ),
					),
				),
				'fetched' => time(),
				'errored' => false,
			)
		);

		$settings = $this->sut->get_settings();

		foreach ( $this->get_required_settings_keys() as $key ) {
			$this->assertArrayHasKey( $key, $settings, "Expected {$key} to be present in the settings contract." );
		}

		$this->assertSame( array( 'card', 'link' ), $settings['enabled_payment_method_ids'] );
		$this->assertContains( 'card', $settings['available_payment_method_ids'] );
		$this->assertContains( 'affirm', $settings['available_payment_method_ids'] );
		$this->assertContains( 'ideal', $settings['available_payment_method_ids'] );
		$this->assertNotContains( 'woopay', $settings['available_payment_method_ids'] );
		$this->assertTrue( $settings['is_wcpay_enabled'] );
		$this->assertTrue( $settings['is_manual_capture_enabled'] );
		$this->assertTrue( $settings['is_test_mode_enabled'] );
		$this->assertTrue( $settings['is_multi_currency_enabled'] );
		$this->assertTrue( $settings['is_wcpay_subscriptions_enabled'] );
		$this->assertFalse( $settings['is_saved_cards_enabled'] );
		$this->assertTrue( $settings['is_woopay_enabled'] );
		$this->assertTrue( $settings['is_woopay_global_theme_support_enabled'] );
		$this->assertTrue( $settings['show_woopay_incompatibility_notice'] );
		$this->assertSame( 'Custom WooPay message.', $settings['woopay_custom_message'] );
		$this->assertSame( 'file_logo', $settings['woopay_store_logo'] );
		$this->assertSame( array( 'payment_request' ), $settings['express_checkout_product_methods'] );
		$this->assertSame( 'active', $settings['payment_method_statuses']['card_payments']['status'] );
		$this->assertSame( 'unrequested', $settings['payment_method_statuses']['link_payments']['status'] );
		$this->assertSame( array( 'currently_due' => array( 'tos_acceptance.date' ) ), $settings['payment_method_statuses']['link_payments']['requirements'] );
		$this->assertArrayNotHasKey( 'is_stripe_billing_enabled', $settings );
		$this->assertArrayNotHasKey( 'is_migrating_stripe_billing', $settings );
		$this->assertArrayNotHasKey( 'stripe_billing_subscription_count', $settings );
		$this->assertArrayNotHasKey( 'stripe_billing_migrated_count', $settings );
	}

	/**
	 * @testdox Should source account and deposit response fields from cached account data.
	 */
	public function test_get_settings_sources_account_and_deposit_fields_from_cached_account_data(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'account_country'                  => 'GB',
				'account_statement_descriptor'     => 'STALE STORE',
				'account_business_name'            => 'Stale Store',
				'deposit_schedule_interval'        => 'daily',
				'deposit_schedule_weekly_anchor'   => 'monday',
				'deposit_schedule_monthly_anchor'  => 1,
				'deposit_delay_days'               => 7,
				'deposit_status'                   => 'stale',
				'deposit_restrictions'             => array( 'stale' => true ),
				'deposit_completed_waiting_period' => false,
			)
		);
		update_option(
			'wcpay_account_data',
			array(
				'data'    => array(
					'account_id'                 => 'acct_native_test',
					'is_live'                    => true,
					'country'                    => 'US',
					'statement_descriptor'       => 'ACCOUNT STORE',
					'statement_descriptor_kanji' => 'アカウント',
					'statement_descriptor_kana'  => 'アカウント',
					'business_profile'           => array(
						'name'            => 'Account Store',
						'url'             => 'https://account.example',
						'support_address' => array( 'city' => 'San Francisco' ),
						'support_email'   => 'support@account.example',
						'support_phone'   => '+15555550123',
					),
					'branding'                   => array(
						'logo'            => 'file_account_logo',
						'icon'            => 'file_account_icon',
						'primary_color'   => '#123456',
						'secondary_color' => '#abcdef',
					),
					'communications_email'       => 'owner@account.example',
					'store_currencies'           => array(
						'default' => 'usd',
					),
					'deposits'                   => array(
						'interval'                 => 'weekly',
						'weekly_anchor'            => 'friday',
						'monthly_anchor'           => 15,
						'delay_days'               => 2,
						'status'                   => 'restricted',
						'restrictions'             => 'schedule_locked',
						'completed_waiting_period' => true,
					),
					'capabilities'               => array(
						'card_payments' => 'active',
					),
					'fees'                       => array(
						'card' => array(),
					),
				),
				'fetched' => time(),
				'errored' => false,
			)
		);

		$settings = $this->sut->get_settings();

		$this->assertSame( 'US', $settings['account_country'] );
		$this->assertSame( 'ACCOUNT STORE', $settings['account_statement_descriptor'] );
		$this->assertSame( 'アカウント', $settings['account_statement_descriptor_kanji'] );
		$this->assertSame( 'アカウント', $settings['account_statement_descriptor_kana'] );
		$this->assertSame( 'Account Store', $settings['account_business_name'] );
		$this->assertSame( 'https://account.example', $settings['account_business_url'] );
		$this->assertSame( array( 'city' => 'San Francisco' ), $settings['account_business_support_address'] );
		$this->assertSame( 'support@account.example', $settings['account_business_support_email'] );
		$this->assertSame( '+15555550123', $settings['account_business_support_phone'] );
		$this->assertSame( 'file_account_logo', $settings['account_branding_logo'] );
		$this->assertSame( 'file_account_icon', $settings['account_branding_icon'] );
		$this->assertSame( '#123456', $settings['account_branding_primary_color'] );
		$this->assertSame( '#abcdef', $settings['account_branding_secondary_color'] );
		$this->assertSame( 'usd', $settings['account_domestic_currency'] );
		$this->assertSame( 'owner@account.example', $settings['account_communications_email'] );
		$this->assertSame( 'weekly', $settings['deposit_schedule_interval'] );
		$this->assertSame( 'friday', $settings['deposit_schedule_weekly_anchor'] );
		$this->assertSame( 15, $settings['deposit_schedule_monthly_anchor'] );
		$this->assertSame( 2, $settings['deposit_delay_days'] );
		$this->assertSame( 'restricted', $settings['deposit_status'] );
		$this->assertSame( 'schedule_locked', $settings['deposit_restrictions'] );
		$this->assertTrue( $settings['deposit_completed_waiting_period'] );
	}

	/**
	 * @testdox Should persist active gateway settings and refresh the settings response.
	 */
	public function test_update_settings_persists_active_gateway_settings_and_returns_refreshed_contract(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'enabled'                        => 'no',
				'test_mode'                      => 'no',
				'manual_capture'                 => 'no',
				'upe_enabled_payment_method_ids' => array( 'card' ),
			)
		);
		update_option( '_wcpay_feature_subscriptions', '1' );
		update_option( '_wcpay_feature_stripe_billing', '1' );
		update_option(
			'wcpay_account_data',
			array(
				'data'    => array(
					'account_id'   => 'acct_native_test',
					'is_live'      => true,
					'capabilities' => array(
						'card_payments'   => 'active',
						'link_payments'   => 'active',
						'affirm_payments' => 'active',
					),
					'fees'         => array(
						'card'   => array(),
						'link'   => array(),
						'affirm' => array(),
					),
				),
				'fetched' => time(),
				'errored' => false,
			)
		);
		$store_setup_sync_count = did_action( 'wcpay_store_setup_sync' );

		$result = $this->sut->update_settings(
			array(
				'is_wcpay_enabled'                       => true,
				'is_manual_capture_enabled'              => true,
				'is_test_mode_enabled'                   => true,
				'is_debug_log_enabled'                   => true,
				'is_saved_cards_enabled'                 => true,
				'is_payment_request_enabled'             => false,
				'is_express_checkout_in_payment_methods_enabled' => true,
				'payment_request_button_size'            => 'medium',
				'payment_request_button_type'            => 'default',
				'payment_request_button_theme'           => 'light',
				'payment_request_button_border_radius'   => 8,
				'is_woopay_enabled'                      => true,
				'is_woopay_global_theme_support_enabled' => true,
				'woopay_custom_message'                  => 'Pay faster with [terms_of_service_link] and [privacy_policy_link].',
				'woopay_store_logo'                      => 'file_new',
				'enabled_payment_method_ids'             => array( 'card', 'link', 'affirm', 'woopay', 'invalid_method' ),
				'express_checkout_product_methods'       => array( 'payment_request', 'link', 'invalid_method' ),
				'express_checkout_cart_methods'          => array( 'amazon_pay' ),
				'express_checkout_checkout_methods'      => array( 'woopay' ),
				'is_multi_currency_enabled'              => true,
				'is_wcpay_subscriptions_enabled'         => false,
				'is_stripe_billing_enabled'              => false,
			)
		);

		$stored = get_option( 'woocommerce_woocommerce_payments_settings' );

		$this->assertIsArray( $stored );
		$this->assertSame( 'yes', $stored['enabled'] );
		$this->assertSame( 'yes', $stored['manual_capture'] );
		$this->assertSame( 'yes', $stored['test_mode'] );
		$this->assertSame( 'yes', $stored['enable_logging'] );
		$this->assertSame( 'yes', $stored['saved_cards'] );
		$this->assertSame( 'no', $stored['payment_request'] );
		$this->assertSame( 'yes', $stored['express_checkout_in_payment_methods'] );
		$this->assertSame( 'medium', $stored['payment_request_button_size'] );
		$this->assertSame( 'default', $stored['payment_request_button_type'] );
		$this->assertSame( 'light', $stored['payment_request_button_theme'] );
		$this->assertSame( 8, $stored['payment_request_button_border_radius'] );
		$this->assertSame( 'yes', $stored['platform_checkout'] );
		$this->assertSame( 'yes', $stored['is_woopay_global_theme_support_enabled'] );
		$this->assertSame( 'Pay faster with [terms] and [privacy_policy].', $stored['platform_checkout_custom_message'] );
		$this->assertSame( 'file_new', $stored['platform_checkout_store_logo'] );
		$this->assertSame( array( 'card', 'link' ), $stored['upe_enabled_payment_method_ids'] );
		$this->assertSame( array( 'payment_request', 'link' ), $stored['express_checkout_product_methods'] );
		$this->assertSame( array( 'amazon_pay' ), $stored['express_checkout_cart_methods'] );
		$this->assertSame( array( 'woopay' ), $stored['express_checkout_checkout_methods'] );
		$this->assertSame( '1', get_option( '_wcpay_feature_customer_multi_currency' ) );
		$this->assertSame( '0', get_option( '_wcpay_feature_subscriptions' ) );
		$this->assertSame( '1', get_option( '_wcpay_feature_stripe_billing' ), 'Native settings must not mutate the Stripe Billing flag.' );
		$this->assertSame( $stored['upe_enabled_payment_method_ids'], $result['enabled_payment_method_ids'] );
		$this->assertSame( $store_setup_sync_count + 1, did_action( 'wcpay_store_setup_sync' ) );
	}

	/**
	 * @testdox Should preserve test mode and debug log settings while WooPayments dev mode is active.
	 */
	public function test_update_settings_preserves_test_mode_and_debug_log_settings_in_dev_mode(): void {
		add_filter( 'wcpay_dev_mode', '__return_true' );
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'enabled'        => 'no',
				'test_mode'      => 'no',
				'enable_logging' => 'no',
			)
		);

		$result = $this->sut->update_settings(
			array(
				'is_wcpay_enabled'     => true,
				'is_test_mode_enabled' => true,
				'is_debug_log_enabled' => true,
			)
		);

		$stored = get_option( 'woocommerce_woocommerce_payments_settings' );

		$this->assertIsArray( $result );
		$this->assertIsArray( $stored );
		$this->assertSame( 'yes', $stored['enabled'] );
		$this->assertSame( 'no', $stored['test_mode'] );
		$this->assertSame( 'no', $stored['enable_logging'] );
		$this->assertTrue( $result['is_dev_mode_enabled'] );
		$this->assertTrue( $result['is_test_mode_enabled'] );
	}

	/**
	 * @testdox Should propagate provider-backed account settings through the native API client.
	 */
	public function test_update_settings_persists_provider_backed_account_settings_through_api_client(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'account_statement_descriptor'       => 'OLD STORE',
				'account_business_support_email'     => 'old@example.test',
				'deposit_schedule_interval'          => 'daily',
				'deposit_schedule_weekly_anchor'     => 'monday',
				'deposit_schedule_monthly_anchor'    => 1,
				'advanced_fraud_protection_settings' => array(),
			)
		);

		$result = $this->sut->update_settings(
			array(
				'account_statement_descriptor'       => 'NATIVE STORE',
				'account_statement_descriptor_kanji' => 'ネイティブ',
				'account_statement_descriptor_kana'  => 'ネイティブ',
				'account_business_support_email'     => 'support@example.test',
				'deposit_schedule_interval'          => 'weekly',
				'deposit_schedule_weekly_anchor'     => 'friday',
				'deposit_schedule_monthly_anchor'    => 15,
				'current_protection_level'           => 'advanced',
				'advanced_fraud_protection_settings' => array(
					array(
						'key'     => 'avs_verification',
						'outcome' => 'block',
						'check'   => array(
							'key'      => 'cvc_check',
							'operator' => 'equals',
							'value'    => 'fail',
						),
					),
				),
			)
		);

		$stored = get_option( 'woocommerce_woocommerce_payments_settings' );

		$this->assertIsArray( $result );
		$this->assertSame(
			array(
				'statement_descriptor'            => 'NATIVE STORE',
				'statement_descriptor_kanji'      => 'ネイティブ',
				'statement_descriptor_kana'       => 'ネイティブ',
				'business_support_email'          => 'support@example.test',
				'deposit_schedule_interval'       => 'weekly',
				'deposit_schedule_monthly_anchor' => 15,
				'deposit_schedule_weekly_anchor'  => 'friday',
			),
			$this->api_client->last_account_settings
		);
		$this->assertSame(
			array(
				array(
					'key'     => 'avs_verification',
					'outcome' => 'block',
					'check'   => array(
						'key'      => 'cvc_check',
						'operator' => 'equals',
						'value'    => 'fail',
					),
				),
			),
			$this->api_client->last_fraud_ruleset
		);
		$this->assertIsArray( $stored );
		$this->assertSame( 'NATIVE STORE', $stored['account_statement_descriptor'] );
		$this->assertSame( 'support@example.test', $stored['account_business_support_email'] );
		$this->assertSame( 'advanced', get_option( 'current_protection_level' ) );
		$this->assertSame( $this->api_client->last_fraud_ruleset, get_transient( 'wcpay_fraud_protection_settings' ) );
	}

	/**
	 * @testdox Should save canonical fraud presets instead of stale advanced rules.
	 */
	public function test_update_settings_saves_canonical_fraud_presets(): void {
		update_option( 'current_protection_level', 'advanced' );
		set_transient(
			'wcpay_fraud_protection_settings',
			array(
				array(
					'key'     => 'custom_rule',
					'outcome' => 'allow',
					'check'   => array(
						'key'      => 'item_count',
						'operator' => 'greater_than',
						'value'    => 1,
					),
				),
			),
			DAY_IN_SECONDS
		);

		$result = $this->sut->update_settings(
			array(
				'current_protection_level'           => 'standard',
				'advanced_fraud_protection_settings' => array(
					array(
						'key'     => 'custom_rule',
						'outcome' => 'allow',
						'check'   => array(
							'key'      => 'item_count',
							'operator' => 'greater_than',
							'value'    => 1,
						),
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'standard', get_option( 'current_protection_level' ) );
		$this->assertCount( 4, $this->api_client->last_fraud_ruleset );
		$this->assertNotContains( 'custom_rule', wp_list_pluck( $this->api_client->last_fraud_ruleset, 'key' ) );
	}

	/**
	 * @testdox Should include the current payout interval when only a payout anchor changes.
	 */
	public function test_update_settings_includes_payout_interval_for_anchor_updates(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'deposit_schedule_interval'       => 'weekly',
				'deposit_schedule_weekly_anchor'  => 'monday',
				'deposit_schedule_monthly_anchor' => 1,
			)
		);

		$result = $this->sut->update_settings(
			array(
				'deposit_schedule_weekly_anchor' => 'friday',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame(
			array(
				'deposit_schedule_weekly_anchor' => 'friday',
				'deposit_schedule_interval'      => 'weekly',
			),
			$this->api_client->last_account_settings
		);
	}

	/**
	 * @testdox Should request unrequested payment method capabilities when enabling methods.
	 */
	public function test_update_settings_requests_unrequested_payment_method_capabilities(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'upe_enabled_payment_method_ids' => array( 'card' ),
			)
		);
		update_option(
			'wcpay_account_data',
			array(
				'data'    => array(
					'account_id'   => 'acct_native_test',
					'is_live'      => true,
					'capabilities' => array(
						'card_payments' => 'active',
						'link_payments' => 'unrequested',
					),
					'fees'         => array(
						'card' => array(),
						'link' => array(),
					),
				),
				'fetched' => time(),
				'errored' => false,
			)
		);

		$result = $this->sut->update_settings(
			array(
				'enabled_payment_method_ids' => array( 'card', 'link' ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame(
			array(
				array(
					'capability_id' => 'link_payments',
					'requested'     => true,
				),
			),
			$this->api_client->capability_requests
		);
	}

	/**
	 * @testdox Should deduplicate payment methods before requesting capabilities.
	 */
	public function test_update_settings_deduplicates_payment_methods_before_requesting_capabilities(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'upe_enabled_payment_method_ids' => array( 'card' ),
			)
		);
		update_option(
			'wcpay_account_data',
			array(
				'data'    => array(
					'account_id'   => 'acct_native_test',
					'is_live'      => true,
					'capabilities' => array(
						'card_payments' => 'active',
						'link_payments' => 'unrequested',
					),
					'fees'         => array(
						'card' => array(),
						'link' => array(),
					),
				),
				'fetched' => time(),
				'errored' => false,
			)
		);

		$result = $this->sut->update_settings(
			array(
				'enabled_payment_method_ids' => array( 'card', 'link', 'link', 'link' ),
			)
		);
		$stored = get_option( 'woocommerce_woocommerce_payments_settings' );

		$this->assertIsArray( $result );
		$this->assertIsArray( $stored );
		$this->assertSame( array( 'card', 'link' ), $stored['upe_enabled_payment_method_ids'] );
		$this->assertSame(
			array(
				array(
					'capability_id' => 'link_payments',
					'requested'     => true,
				),
			),
			$this->api_client->capability_requests
		);
	}

	/**
	 * @testdox Should not write account settings when posted account fields match the cached account.
	 */
	public function test_update_settings_diffs_account_fields_against_cached_account_data(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'saved_cards'                  => 'no',
				'account_statement_descriptor' => 'STALE LOCAL',
			)
		);
		update_option(
			'wcpay_account_data',
			array(
				'data'    => array(
					'account_id'           => 'acct_native_test',
					'is_live'              => true,
					'statement_descriptor' => 'NATIVE STORE',
				),
				'fetched' => time(),
				'errored' => false,
			)
		);

		$result = $this->sut->update_settings(
			array(
				'is_saved_cards_enabled'       => true,
				'account_statement_descriptor' => 'NATIVE STORE',
			)
		);
		$stored = get_option( 'woocommerce_woocommerce_payments_settings' );

		$this->assertIsArray( $result );
		$this->assertIsArray( $stored );
		$this->assertSame( 'yes', $stored['saved_cards'] );
		$this->assertNull( $this->api_client->last_account_settings );
	}

	/**
	 * @testdox Should reject disallowed option updates and validate allowed option value types.
	 */
	public function test_update_option_rejects_disallowed_options_and_invalid_value_types(): void {
		$invalid_name_result = $this->sut->update_option( 'not_allowed', true );
		$invalid_type_result = $this->sut->update_option( 'wcpay_fraud_protection_welcome_tour_dismissed', 'yes' );
		$valid_bool_result   = $this->sut->update_option( 'wcpay_fraud_protection_welcome_tour_dismissed', true );
		$valid_array_result  = $this->sut->update_option( 'wcpay_duplicate_payment_method_notices_dismissed', array( 'card' ) );

		$this->assertInstanceOf( WP_Error::class, $invalid_name_result );
		$this->assertSame( 400, $invalid_name_result->get_error_data()['status'] );
		$this->assertInstanceOf( WP_Error::class, $invalid_type_result );
		$this->assertSame( 400, $invalid_type_result->get_error_data()['status'] );
		$this->assertTrue( $valid_bool_result );
		$this->assertTrue( $valid_array_result );
		$this->assertTrue( get_option( 'wcpay_fraud_protection_welcome_tour_dismissed' ) );
		$this->assertSame( array( 'card' ), get_option( 'wcpay_duplicate_payment_method_notices_dismissed' ) );
	}

	/**
	 * @testdox Should delegate file upload to the native API client.
	 */
	public function test_upload_file_delegates_to_native_api_client(): void {
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/file' );
		$request->set_param( 'purpose', 'business_logo' );

		$result = $this->sut->upload_file( $request );

		$this->assertSame( array( 'id' => 'file_test_logo' ), $result );
		$this->assertSame( $request, $this->api_client->last_upload_request );
	}

	/**
	 * @testdox Should delegate file details and contents to the native API client.
	 */
	public function test_get_file_details_and_contents_delegate_to_native_api_client(): void {
		$file     = $this->sut->get_file( 'file_test_logo', false );
		$contents = $this->sut->get_file_contents( 'file_test_logo', false );

		$this->assertSame( $this->api_client->file_response, $file );
		$this->assertSame( 'file_test_logo', $this->api_client->last_file_id );
		$this->assertFalse( $this->api_client->last_file_as_account );
		$this->assertSame( $this->api_client->file_contents_response, $contents );
		$this->assertSame( 'file_test_logo', $this->api_client->last_file_contents_id );
		$this->assertFalse( $this->api_client->last_file_contents_as_account );
	}

	/**
	 * Create a native account service.
	 *
	 * @return WooPaymentsAccountService
	 */
	private function create_account_service(): WooPaymentsAccountService {
		$account_service = new WooPaymentsAccountService();
		$account_service->init( new LegacyProxy() );

		return $account_service;
	}

	/**
	 * Get the settings keys required by the hoisted settings store.
	 *
	 * @return string[]
	 */
	private function get_required_settings_keys(): array {
		return array(
			'enabled_payment_method_ids',
			'available_payment_method_ids',
			'payment_method_statuses',
			'duplicated_payment_method_ids',
			'is_wcpay_enabled',
			'is_manual_capture_enabled',
			'is_test_mode_enabled',
			'is_test_mode_onboarding',
			'is_dev_mode_enabled',
			'is_multi_currency_enabled',
			'is_wcpay_subscriptions_enabled',
			'is_wcpay_subscriptions_eligible',
			'is_subscriptions_plugin_active',
			'account_country',
			'account_statement_descriptor',
			'account_statement_descriptor_kanji',
			'account_statement_descriptor_kana',
			'account_business_name',
			'account_business_url',
			'account_business_support_address',
			'account_business_support_email',
			'account_business_support_phone',
			'account_branding_logo',
			'account_branding_icon',
			'account_branding_primary_color',
			'account_branding_secondary_color',
			'account_domestic_currency',
			'account_communications_email',
			'is_payment_request_enabled',
			'is_express_checkout_in_payment_methods_enabled',
			'is_debug_log_enabled',
			'payment_request_button_size',
			'payment_request_button_type',
			'payment_request_button_theme',
			'payment_request_button_border_radius',
			'is_saved_cards_enabled',
			'is_card_present_eligible',
			'is_woopay_enabled',
			'is_woopay_global_theme_support_enabled',
			'show_woopay_incompatibility_notice',
			'woopay_custom_message',
			'woopay_store_logo',
			'deposit_schedule_interval',
			'deposit_schedule_monthly_anchor',
			'deposit_schedule_weekly_anchor',
			'deposit_delay_days',
			'deposit_status',
			'deposit_restrictions',
			'deposit_completed_waiting_period',
			'current_protection_level',
			'advanced_fraud_protection_settings',
			'express_checkout_product_methods',
			'express_checkout_cart_methods',
			'express_checkout_checkout_methods',
		);
	}
}
