<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFrontendStylesService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFrontendTrackingController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWooPaySessionService;
use WC_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for the WooPaymentsWooPaySessionService class.
 */
class WooPaymentsWooPaySessionServiceTest extends WC_Unit_Test_Case {

	/**
	 * Original WooCommerce session object.
	 *
	 * @var object|null
	 */
	private $original_session;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_session = WC()->session;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		WC()->session = $this->original_session;
		wc_empty_cart();
		delete_option( 'wcpay_woopay_checkout_appearance' );
		delete_option( 'wcpay_styles_cache_version' );
		remove_theme_mod( 'custom_logo' );
		if ( class_exists( '\Jetpack_Options' ) ) {
			\Jetpack_Options::delete_option(
				array(
					'blog_token',
					'id',
					'time_diff',
				)
			);
		}
		remove_all_filters( 'woocommerce_native_woopayments_woopay_blog_id' );
		remove_all_filters( 'woocommerce_native_woopayments_woopay_blog_token' );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'rest_pre_dispatch' );
		remove_all_filters( 'woocommerce_store_api_disable_nonce_check' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Should build WooPay REST URLs from the default host.
	 */
	public function test_builds_woopay_rest_urls_from_default_host(): void {
		$sut = $this->create_service();

		$this->assertSame( 'https://pay.woo.com', $sut->get_woopay_url() );
		$this->assertSame( 'https://pay.woo.com/wp-json/platform-checkout/v1/init', $sut->get_woopay_rest_url( 'init' ) );
	}

	/**
	 * @testdox Should require platform checkout to treat WooPay as enabled.
	 */
	public function test_woopay_requires_platform_checkout_to_be_enabled(): void {
		$sut = $this->create_service(
			array(
				'platform_checkout'                 => 'no',
				'express_checkout_product_methods'  => array( 'payment_request' ),
				'express_checkout_cart_methods'     => array(),
				'express_checkout_checkout_methods' => array( 'woopay' ),
			)
		);

		$this->assertFalse( $sut->is_woopay_enabled() );

		$sut = $this->create_service(
			array(
				'platform_checkout'                 => 'yes',
				'express_checkout_product_methods'  => array( 'payment_request' ),
				'express_checkout_cart_methods'     => array(),
				'express_checkout_checkout_methods' => array(),
			)
		);

		$this->assertTrue( $sut->is_woopay_enabled() );

		$sut = $this->create_service(
			array(
				'platform_checkout' => 'yes',
			),
			array(
				'platform_checkout_eligible' => false,
			)
		);

		$this->assertFalse( $sut->is_woopay_enabled() );
		$this->assertFalse( $sut->get_woopay_frontend_config( 'checkout' )['isWooPayEnabled'] );
	}

	/**
	 * @testdox Should not show the checkout WooPay button unless the checkout express method is enabled.
	 */
	public function test_woopay_checkout_button_requires_checkout_express_method(): void {
		$sut = $this->create_service(
			array(
				'platform_checkout'                 => 'yes',
				'express_checkout_product_methods'  => array( 'payment_request' ),
				'express_checkout_cart_methods'     => array(),
				'express_checkout_checkout_methods' => array(),
			)
		);

		$config = $sut->get_woopay_frontend_config( 'checkout' );

		$this->assertTrue( $config['isWooPayEnabled'] );
		$this->assertFalse( $config['shouldShowWooPayButton'] );
	}

	/**
	 * @testdox Should generate the reference WooPay request signature.
	 */
	public function test_generates_reference_request_signature(): void {
		add_filter( 'woocommerce_native_woopayments_woopay_blog_id', static fn() => '12345' );
		add_filter( 'woocommerce_native_woopayments_woopay_blog_token', static fn() => 'blog-token' );

		$sut      = $this->create_service();
		$expected = hash_hmac( 'sha512', '12345' . floor( time() / 30 ), 'blog-token' );

		$this->assertSame( $expected, $sut->get_woopay_request_signature() );
	}

	/**
	 * @testdox Should preserve the WooPay string user session in init payloads.
	 */
	public function test_preserves_string_user_session_in_init_payloads(): void {
		$sut    = $this->create_service();
		$result = $sut->get_init_session_request( 'shopper@example.com', 'qwerty123' );

		$this->assertSame( 'qwerty123', $result['user_session'] );
	}

	/**
	 * @testdox Should fall back to the theme custom logo in WooPay init session store data.
	 */
	public function test_init_session_request_uses_theme_custom_logo_when_woopay_logo_is_not_configured(): void {
		$custom_logo_id = 123;
		$filter         = static function ( $downsize, $id, $size ) use ( $custom_logo_id ) {
			if ( $custom_logo_id === $id && 'full' === $size ) {
				return array( 'https://example.test/theme-logo.png', 128, 64, false );
			}

			return $downsize;
		};
		add_filter( 'image_downsize', $filter, 10, 3 );
		set_theme_mod( 'custom_logo', $custom_logo_id );

		try {
			$sut    = $this->create_service();
			$result = $sut->get_init_session_request( 'shopper@example.com', 'qwerty123' );

			$this->assertSame( 'https://example.test/theme-logo.png', $result['store_data']['store_logo'] );
		} finally {
			remove_filter( 'image_downsize', $filter, 10 );
		}
	}

	/**
	 * @testdox Should send the WooPay store logo file URL when a custom logo is configured.
	 */
	public function test_init_session_request_uses_configured_woopay_store_logo_file_url(): void {
		$sut = $this->create_service(
			array(
				'platform_checkout_store_logo' => 'file_logo',
			)
		);

		$result = $sut->get_init_session_request( 'shopper@example.com', 'qwerty123' );

		$this->assertSame( get_rest_url( null, 'wc/v3/payments/file/file_logo' ), $result['store_data']['store_logo'] );
	}

	/**
	 * @testdox Should preload current Store API cart and checkout data for WooPay init sessions.
	 */
	public function test_preloads_current_store_api_cart_and_checkout_data(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'    => 'WooPay Preload Product',
				'virtual' => true,
			)
		);
		wc_empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$sut     = $this->create_service();
		$result  = $sut->get_init_session_request( 'shopper@example.com', 'qwerty123' );
		$preload = $result['preloaded_requests'];

		$this->assertSame( 1, $preload['cart']['items_count'] );
		$this->assertSame( $product->get_id(), $preload['cart']['items'][0]['id'] );
		$this->assertArrayHasKey( 'billing_address', $preload['checkout'] );
		$this->assertArrayHasKey( 'order_id', $preload['checkout'] );
	}

	/**
	 * @testdox Should forward WooPay Cart-Token to Store API preload subrequests.
	 */
	public function test_forwards_woopay_cart_token_to_store_api_preload_subrequests(): void {
		$seen_headers = array();
		add_filter(
			'rest_pre_dispatch',
			static function ( $preempt, $server, WP_REST_Request $request ) use ( &$seen_headers ) {
				unset( $server );
				if ( ! in_array( $request->get_route(), array( '/wc/store/v1/cart', '/wc/store/v1/checkout' ), true ) ) {
					return $preempt;
				}

				$seen_headers[ $request->get_route() ] = $request->get_header( 'Cart-Token' );

				return new WP_REST_Response(
					'/wc/store/v1/cart' === $request->get_route()
						? array(
							'items_count' => 1,
							'items'       => array(
								array( 'id' => 123 ),
							),
						)
						: array(
							'order_id'        => 456,
							'payment_methods' => array( 'woocommerce_payments' ),
						)
				);
			},
			10,
			3
		);

		$request = new WP_REST_Request( 'GET', '/payments/woopay/session' );
		$request->set_header( 'Cart-Token', 'cart-token-123' );

		$sut     = $this->create_service();
		$result  = $sut->get_session_data( 'shopper@example.com', $request );
		$preload = $result['preloaded_requests'];

		$this->assertSame( 'cart-token-123', $seen_headers['/wc/store/v1/cart'] );
		$this->assertSame( 'cart-token-123', $seen_headers['/wc/store/v1/checkout'] );
		$this->assertSame( 1, $preload['cart']['items_count'] );
		$this->assertSame( array( 'woocommerce_payments' ), $preload['checkout']['payment_methods'] );
	}

	/**
	 * @testdox Should encrypt minimum session data with the WooPay reference shape.
	 */
	public function test_encrypts_minimum_session_data_with_reference_shape(): void {
		add_filter( 'woocommerce_native_woopayments_woopay_blog_id', static fn() => '12345' );
		add_filter( 'woocommerce_native_woopayments_woopay_blog_token', static fn() => 'blog-token' );

		$sut    = $this->create_service();
		$result = $sut->get_encrypted_minimum_session_data();

		$this->assertSame( '12345', $result['blog_id'] );
		$this->assertArrayHasKey( 'session', $result['data'] );
		$this->assertArrayHasKey( 'iv', $result['data'] );
		$this->assertArrayHasKey( 'hash', $result['data'] );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Verifies WooPay encrypted payload encoding.
		$this->assertNotSame( '', base64_decode( $result['data']['session'], true ) );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Verifies WooPay encrypted payload encoding.
		$this->assertNotSame( false, base64_decode( $result['data']['iv'], true ) );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Verifies WooPay encrypted payload encoding.
		$this->assertNotSame( false, base64_decode( $result['data']['hash'], true ) );
	}

	/**
	 * @testdox Should persist and clear WooPay phone session data.
	 */
	public function test_persists_and_clears_woopay_phone_session_data(): void {
		$this->original_session = WC()->session;
		WC()->session           = $this->create_session();
		$sut                    = $this->create_service();

		$sut->set_woopay_phone_session_data(
			array(
				'save_user_in_woopay'     => 'true',
				'woopay_source_url'       => 'https://example.test/checkout',
				'woopay_is_blocks'        => 'yes',
				'woopay_viewport'         => 'desktop',
				'woopay_user_phone_field' => array(
					'full' => '+15555550123',
				),
			)
		);

		$this->assertSame(
			array(
				'save_user_in_woopay'     => true,
				'woopay_source_url'       => 'https://example.test/checkout',
				'woopay_is_blocks'        => true,
				'woopay_viewport'         => 'desktop',
				'woopay_user_phone_field' => array(
					'full' => '+15555550123',
				),
			),
			WC()->session->get( 'woopay-user-data' )
		);

		$sut->clear_woopay_session_data();

		$this->assertNull( WC()->session->get( 'woopay-user-data' ) );
	}

	/**
	 * @testdox Should preserve legacy flat WooPay phone values while accepting the frontend payload shape.
	 */
	public function test_persists_legacy_flat_woopay_phone_session_data(): void {
		$this->original_session = WC()->session;
		WC()->session           = $this->create_session();
		$sut                    = $this->create_service();

		$sut->set_woopay_phone_session_data(
			array(
				'phone_number' => '+15555550124',
			)
		);

		$this->assertSame(
			'+15555550124',
			WC()->session->get( 'woopay-user-data' )['woopay_user_phone_field']['full']
		);
	}

	/**
	 * @testdox Should build the preserved WooPay checkout frontend config.
	 */
	public function test_builds_woopay_checkout_frontend_config(): void {
		add_filter( 'woocommerce_native_woopayments_woopay_blog_id', static fn() => '12345' );

		$sut = $this->create_service();
		$sut->save_woopay_appearance(
			array( 'theme' => 'stripe' ),
			array(
				array( 'cssSrc' => 'https://fonts.googleapis.com/css2?family=Inter' ),
			)
		);

		$config = $sut->get_woopay_frontend_config( 'checkout' );

		$this->assertTrue( $config['isWooPayEnabled'] );
		$this->assertTrue( $config['isWoopayExpressCheckoutEnabled'] );
		$this->assertTrue( $config['isWooPayEmailInputEnabled'] );
		$this->assertFalse( $config['isWooPayDirectCheckoutEnabled'] );
		$this->assertFalse( $config['isWooPayGlobalThemeSupportEnabled'] );
		$this->assertFalse( $config['forceNetworkSavedCards'] );
		$this->assertSame( 'https://pay.woo.com', $config['woopayHost'] );
		$this->assertSame( '12345', $config['woopayMerchantId'] );
		$this->assertSame( 'checkout', $config['woopayButton']['context'] );
		$this->assertSame( 'default', $config['woopayButton']['type'] );
		$this->assertSame( 'dark', $config['woopayButton']['theme'] );
		$this->assertSame( '48', $config['woopayButton']['height'] );
		$this->assertSame( '', $config['woopayButton']['radius'] );
		$this->assertArrayHasKey( 'woopayButtonNonce', $config );
		$this->assertArrayHasKey( 'addToCartNonce', $config );
		$this->assertTrue( $config['shouldShowWooPayButton'] );
		$this->assertTrue( $config['woopayIsCountryAvailable'] );
		$this->assertNull( $config['woopayAppearance'] );
		$this->assertSame( array(), $config['woopayFontRules'] );
		$this->assertSame( 'Securely save my information for 1-click checkout', $config['woopaySaveUserLabel'] );
		$this->assertSame( 'Mobile phone number', $config['woopayPhoneLabel'] );
		$this->assertArrayHasKey( 'platformTrackerNonce', $config );
		$this->assertSame( admin_url( 'admin-ajax.php' ), $config['ajaxUrl'] );
		$this->assertArrayHasKey( 'isShopperTrackingEnabled', $config );
		$this->assertArrayHasKey( 'is_shopper_tracking_enabled', $config );
	}

	/**
	 * @testdox Should include the current frontend style extractor schema in the cache version.
	 */
	public function test_frontend_styles_cache_version_includes_current_extractor_schema(): void {
		update_option( 'wcpay_styles_cache_version', 'version-one' );

		$service = new WooPaymentsFrontendStylesService();

		$this->assertSame( 'version-one|appearance-extractor-v3', $service->get_styles_cache_version() );
	}

	/**
	 * @testdox Should enable WooPay first-party auth only when WooPay express checkout is available.
	 */
	public function test_first_party_auth_flag_requires_available_woopay_express_checkout(): void {
		$this->assertTrue(
			$this->create_service()->get_woopay_frontend_config( 'checkout' )['isWoopayFirstPartyAuthEnabled'],
			'US eligible WooPay checkout should enable first-party auth.'
		);

		$this->assertFalse(
			$this->create_service( array(), array( 'country' => 'CA' ) )->get_woopay_frontend_config( 'checkout' )['isWoopayFirstPartyAuthEnabled'],
			'Non-US WooPay accounts should not enable first-party auth.'
		);

		$this->assertFalse(
			$this->create_service( array(), array( 'platform_checkout_eligible' => false ) )->get_woopay_frontend_config( 'checkout' )['isWoopayFirstPartyAuthEnabled'],
			'Accounts that are not eligible for platform checkout should not enable first-party auth.'
		);

		$this->assertFalse(
			$this->create_service( array( 'platform_checkout' => 'no' ) )->get_woopay_frontend_config( 'checkout' )['isWoopayFirstPartyAuthEnabled'],
			'Stores with WooPay disabled should not enable first-party auth.'
		);

		$this->assertFalse(
			$this->create_service( array( 'express_checkout_checkout_methods' => array() ) )->get_woopay_frontend_config( 'checkout' )['isWoopayFirstPartyAuthEnabled'],
			'Checkout contexts without WooPay express checkout should not enable first-party auth.'
		);
	}

	/**
	 * @testdox Should match the extension WooPay button style settings.
	 */
	public function test_uses_extension_woopay_button_style_settings(): void {
		$sut = $this->create_service(
			array(
				'payment_request_button_size'          => 'medium',
				'payment_request_button_height'        => '44',
				'payment_request_button_border_radius' => '',
				'payment_request_button_radius'        => '9',
			)
		);

		$config = $sut->get_woopay_frontend_config( 'checkout' );

		$this->assertSame( '48', $config['woopayButton']['height'] );
		$this->assertSame( '', $config['woopayButton']['radius'] );
	}

	/**
	 * @testdox Should build WooPay express checkout params with Core-owned account data.
	 */
	public function test_builds_woopay_express_checkout_params(): void {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_currency', 'USD' );

		$sut    = $this->create_service();
		$params = $sut->get_express_checkout_params( 'checkout' );

		$this->assertSame( 'pk_test_123', $params['stripe']['publishableKey'] );
		$this->assertSame( 'acct_123', $params['stripe']['accountId'] );
		$this->assertSame( 'checkout', $params['button_context'] );
		$this->assertSame( array( 'woopay' ), $params['enabled_methods'] );
		$this->assertSame( get_bloginfo( 'name' ), $params['store_name'] );
		$this->assertSame( 'usd', $params['checkout']['currency_code'] );
		$this->assertSame( 2, $params['checkout']['stripe_minor_unit'] );
		$this->assertSame( 'US', $params['checkout']['country_code'] );
		$this->assertSame( 'default', $params['button']['type'] );
		$this->assertArrayHasKey( 'isEceUsingConfirmationTokens', $params['flags'] );
	}

	/**
	 * @testdox Should build WooPay express checkout params with Stripe zero-decimal currency units.
	 */
	public function test_woopay_express_checkout_params_use_stripe_zero_minor_unit_for_zero_decimal_currency(): void {
		update_option( 'woocommerce_default_country', 'JP' );
		update_option( 'woocommerce_currency', 'JPY' );

		$params = $this->create_service()->get_express_checkout_params( 'checkout' );

		$this->assertSame( 'jpy', $params['checkout']['currency_code'] );
		$this->assertSame( 0, $params['checkout']['stripe_minor_unit'] );
	}

	/**
	 * @testdox Should build WooPay express checkout params with Stripe two-decimal special-case currency units.
	 */
	public function test_woopay_express_checkout_params_keep_stripe_two_minor_unit_for_special_case_currency(): void {
		update_option( 'woocommerce_default_country', 'UG' );
		update_option( 'woocommerce_currency', 'UGX' );

		$params = $this->create_service()->get_express_checkout_params( 'checkout' );

		$this->assertSame( 'ugx', $params['checkout']['currency_code'] );
		$this->assertSame( 2, $params['checkout']['stripe_minor_unit'] );
	}

	/**
	 * @testdox Should build WooPay save-user checkout data from the account cache.
	 */
	public function test_builds_woopay_save_user_checkout_data(): void {
		$sut = $this->create_service(
			array(),
			array(
				'country'                    => 'US',
				'pre_check_save_my_info'     => true,
				'platform_checkout_eligible' => true,
			)
		);

		$this->assertSame( array( 'PRE_CHECK_SAVE_MY_INFO' => true ), $sut->get_save_user_checkout_data() );
	}

	/**
	 * @testdox Should add WooPay save-user session data to order metadata.
	 */
	public function test_adds_woopay_session_data_to_order_metadata(): void {
		$this->original_session = WC()->session;
		WC()->session           = $this->create_session();
		WC()->session->set(
			'woopay-user-data',
			array(
				'save_user_in_woopay'     => true,
				'woopay_source_url'       => 'https://example.test/checkout',
				'woopay_is_blocks'        => true,
				'woopay_viewport'         => 'desktop',
				'woopay_user_phone_field' => array(
					'full' => '+15555550123',
				),
			)
		);

		$order = \WC_Helper_Order::create_order();
		$order->set_billing_first_name( 'Ada' );
		$order->set_billing_last_name( 'Lovelace' );
		$order->set_billing_phone( '+15555550000' );
		$order->set_billing_company( 'Analytical Engines' );
		$order->set_shipping_first_name( 'Grace' );
		$order->set_shipping_last_name( 'Hopper' );
		$order->set_shipping_phone( '+15555550001' );
		$order->set_shipping_company( 'Compilers Inc' );

		$sut      = $this->create_service();
		$metadata = $sut->maybe_add_woopay_user_metadata( array( 'existing' => 'kept' ), $order );

		$this->assertSame( 'kept', $metadata['existing'] );
		$this->assertSame( 'Ada', $metadata['platform_checkout_primary_first_name'] );
		$this->assertSame( 'Lovelace', $metadata['platform_checkout_primary_last_name'] );
		$this->assertSame( '+15555550000', $metadata['platform_checkout_primary_phone'] );
		$this->assertSame( 'Analytical Engines', $metadata['platform_checkout_primary_company'] );
		$this->assertSame( 'Grace', $metadata['platform_checkout_secondary_first_name'] );
		$this->assertSame( 'Hopper', $metadata['platform_checkout_secondary_last_name'] );
		$this->assertSame( '+15555550001', $metadata['platform_checkout_secondary_phone'] );
		$this->assertSame( 'Compilers Inc', $metadata['platform_checkout_secondary_company'] );
		$this->assertSame( '+15555550123', $metadata['platform_checkout_phone'] );
		$this->assertSame( 'https://example.test/checkout', $metadata['platform_checkout_source_url'] );
		$this->assertTrue( $metadata['platform_checkout_is_blocks'] );
		$this->assertSame( 'desktop', $metadata['platform_checkout_viewport'] );
	}

	/**
	 * @testdox Should store WooPay checkout appearance in the preserved option shape.
	 */
	public function test_stores_appearance_in_preserved_option_shape(): void {
		$sut        = $this->create_service();
		$appearance = $this->get_valid_appearance();
		$font_rules = array(
			array(
				'cssSrc' => 'https://fonts.googleapis.com/css2?family=Inter',
			),
		);

		$sut->save_woopay_appearance( $appearance, $font_rules );

		$stored = get_option( 'wcpay_woopay_checkout_appearance' );
		$this->assertIsArray( $stored );
		$this->assertSame( $appearance, $stored['appearance'] );
		$this->assertSame( $font_rules, $stored['font_rules'] );
		$this->assertNotEmpty( $stored['version'] );
		$this->assertSame( $appearance, $sut->get_woopay_appearance() );
		$this->assertSame( $font_rules, $sut->get_woopay_font_rules() );
	}

	/**
	 * @testdox Should ignore stale WooPay appearance data when the styles cache version changes.
	 */
	public function test_ignores_stale_appearance_when_styles_cache_version_changes(): void {
		$sut        = $this->create_service();
		$appearance = $this->get_valid_appearance();

		update_option( 'wcpay_styles_cache_version', 'version-one' );
		$sut->save_woopay_appearance(
			$appearance,
			array(
				array(
					'cssSrc' => 'https://fonts.googleapis.com/css2?family=Inter',
				),
			)
		);

		update_option( 'wcpay_styles_cache_version', 'version-two' );

		$this->assertSame( array(), $sut->get_woopay_appearance() );
		$this->assertSame( array(), $sut->get_woopay_font_rules() );
		$this->assertTrue(
			$sut->maybe_save_woopay_appearance(
				array(
					'theme'     => 'night',
					'variables' => array(
						'colorText' => '#ffffff',
					),
				)
			)
		);
	}

	/**
	 * @testdox Should ignore appearance stored before the current frontend style extractor schema.
	 */
	public function test_ignores_appearance_stored_before_current_frontend_style_extractor_schema(): void {
		$sut        = $this->create_service();
		$appearance = $this->get_valid_appearance();

		update_option( 'wcpay_styles_cache_version', 'version-one' );
		update_option(
			'wcpay_woopay_checkout_appearance',
			array(
				'appearance' => $appearance,
				'font_rules' => array(),
				'version'    => 'version-one',
			)
		);

		$this->assertSame( array(), $sut->get_woopay_appearance() );
		$this->assertSame( array(), $sut->get_woopay_font_rules() );
	}

	/**
	 * @testdox Should conditionally store shopper appearance only when no appearance exists.
	 */
	public function test_conditionally_stores_shopper_appearance_only_once(): void {
		$sut    = $this->create_service();
		$first  = $this->get_valid_appearance();
		$second = array(
			'theme'     => 'night',
			'variables' => array(
				'colorText' => '#ffffff',
			),
		);

		$this->assertTrue( $sut->maybe_save_woopay_appearance( $first ) );
		$this->assertFalse( $sut->maybe_save_woopay_appearance( $second ) );
		$this->assertSame( $first, $sut->get_woopay_appearance() );
	}

	/**
	 * @testdox Should reject invalid WooPay appearance schema.
	 */
	public function test_rejects_invalid_appearance_schema(): void {
		$sut = $this->create_service();

		$this->assertFalse( $sut->validate_appearance_schema( array( 'buttonTheme' => 'dark' ) ) );
		$this->assertFalse( $sut->maybe_save_woopay_appearance( array( 'buttonTheme' => 'dark' ) ) );
		$this->assertFalse( get_option( 'wcpay_woopay_checkout_appearance' ) );
	}

	/**
	 * @testdox Should forward init session requests through filterable transport.
	 */
	public function test_forwards_init_session_request_through_filterable_transport(): void {
		add_filter( 'woocommerce_native_woopayments_woopay_blog_id', static fn() => '12345' );
		add_filter( 'woocommerce_native_woopayments_woopay_blog_token', static fn() => 'blog-token' );
		if ( class_exists( '\Jetpack_Options' ) ) {
			\Jetpack_Options::update_option( 'id', 12345 );
			\Jetpack_Options::update_option( 'blog_token', 'token-key.blog-token' );
			\Jetpack_Options::update_option( 'time_diff', 0 );
		}

		$captured_request = null;
		add_filter(
			'pre_http_request',
			static function ( $preempt, array $parsed_args, string $url ) use ( &$captured_request ) {
				$captured_request = array(
					'url'     => $url,
					'body'    => json_decode( (string) $parsed_args['body'], true ),
					'headers' => $parsed_args['headers'],
				);

				return array(
					'headers'  => array(),
					'body'     => wp_json_encode( array( 'result' => 'success' ) ),
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			},
			10,
			3
		);

		$appearance = $this->get_valid_appearance();
		$font_rules = array(
			array(
				'cssSrc' => 'https://fonts.wp.com/font.css',
				'family' => 'Inter',
			),
		);

		$expected_font_rules = array(
			array(
				'cssSrc' => 'https://fonts.wp.com/font.css',
			),
		);

		$sut    = $this->create_service();
		$result = $sut->init_woopay_session(
			array(
				'email'        => 'shopper@example.com',
				'user_session' => 'qwerty123',
				'appearance'   => wp_json_encode( $appearance ),
				'font_rules'   => wp_json_encode( $font_rules ),
			)
		);

		$this->assertSame( array( 'result' => 'success' ), $result );
		$this->assertSame( 'https://pay.woo.com/wp-json/platform-checkout/v1/init', strtok( $captured_request['url'], '?' ) );
		$query = wp_parse_url( $captured_request['url'], PHP_URL_QUERY );
		parse_str( is_string( $query ) ? $query : '', $query_args );
		$this->assertArrayHasKey( 'token', $query_args );
		$this->assertArrayHasKey( 'body-hash', $query_args );
		$this->assertArrayHasKey( 'signature', $query_args );
		$this->assertStringStartsWith( 'X_JETPACK ', $captured_request['headers']['Authorization'] );
		$this->assertSame( 'acct_123', $captured_request['body']['store_data']['account_id'] );
		$this->assertTrue( $captured_request['body']['store_data']['test_mode'] );
		$this->assertSame( 'qwerty123', $captured_request['body']['user_session'] );
		$this->assertSame( $appearance, $captured_request['body']['appearance'] );
		$this->assertSame( $expected_font_rules, $captured_request['body']['font_rules'] );
		$this->assertArrayHasKey( 'session_nonce', $captured_request['body'] );
		$this->assertArrayHasKey( 'store_api_token', $captured_request['body'] );
	}

	/**
	 * Create the System Under Test.
	 *
	 * @param array<string,mixed> $settings     Gateway settings.
	 * @param array<string,mixed> $account_data Account data.
	 * @return WooPaymentsWooPaySessionService
	 */
	private function create_service( array $settings = array(), array $account_data = array() ): WooPaymentsWooPaySessionService {
		$settings     = array_merge(
			array(
				'platform_checkout'                    => 'yes',
				'manual_capture'                       => 'no',
				'payment_request_button_type'          => 'default',
				'payment_request_button_theme'         => 'dark',
				'payment_request_button_size'          => 'medium',
				'payment_request_button_height'        => '44',
				'payment_request_button_border_radius' => '',
				'express_checkout_product_methods'     => array( 'woopay' ),
				'express_checkout_cart_methods'        => array( 'woopay' ),
				'express_checkout_checkout_methods'    => array( 'woopay' ),
			),
			$settings
		);
		$account_data = array_merge(
			array(
				'country'                    => 'US',
				'pre_check_save_my_info'     => false,
				'platform_checkout_eligible' => true,
			),
			$account_data
		);

		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_account_id', 'get_publishable_key', 'get_cached_account_data', 'is_test_mode_enabled', 'get_gateway_setting' ) )
			->getMock();

		$account_service->method( 'get_account_id' )->willReturn( 'acct_123' );
		$account_service->method( 'get_publishable_key' )->willReturn( 'pk_test_123' );
		$account_service->method( 'get_cached_account_data' )->willReturn( $account_data );
		$account_service->method( 'is_test_mode_enabled' )->willReturn( true );
		$account_service->method( 'get_gateway_setting' )->willReturnCallback(
			static fn( string $key, $fallback = null ) => array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback
		);

		$tracking_controller = $this->getMockBuilder( WooPaymentsFrontendTrackingController::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_shopper_tracking_enabled' ) )
			->getMock();
		$tracking_controller->method( 'is_shopper_tracking_enabled' )->willReturn( true );

		$sut = new WooPaymentsWooPaySessionService();
		$sut->init( $account_service, new WooPaymentsFrontendStylesService(), $tracking_controller );

		return $sut;
	}

	/**
	 * Get a valid WooPay appearance payload.
	 *
	 * @return array<string,mixed>
	 */
	private function get_valid_appearance(): array {
		return array(
			'theme'     => 'stripe',
			'variables' => array(
				'colorText' => '#111111',
			),
			'rules'     => array(
				'.Input' => array(
					'borderRadius' => '4px',
				),
			),
		);
	}

	/**
	 * Create a WooCommerce session test double.
	 *
	 * @return object
	 */
	private function create_session(): object {
		return new class() extends \WC_Session {
			/**
			 * Session data.
			 *
			 * @var array<string,mixed>
			 */
			protected $_data = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

			/**
			 * Get a session value.
			 *
			 * @param string $key           Session key.
			 * @param mixed  $default_value Default value.
			 * @return mixed
			 */
			public function get( $key, $default_value = null ) {
				return $this->_data[ $key ] ?? $default_value;
			}

			/**
			 * Set a session value.
			 *
			 * @param string $key   Session key.
			 * @param mixed  $value Session value.
			 */
			public function set( $key, $value ) {
				if ( null === $value ) {
					unset( $this->_data[ $key ] );
					return;
				}

				$this->_data[ $key ] = $value;
			}

			/**
			 * Set the customer session cookie.
			 *
			 * @param bool $set Whether to set the cookie.
			 */
			public function set_customer_session_cookie( bool $set ): void {}
		};
	}
}
