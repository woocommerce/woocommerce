<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Helper_Subscriptions_API::activate().
 */
class WC_Helper_Subscriptions_API_Test extends \WC_Unit_Test_Case {

	/**
	 * Slug of a plugin that is always present but inactive in the test
	 * environment, used to simulate an "installed but not active" Woo
	 * subscription without depending on WooCommerce's own plugin state.
	 */
	private const INACTIVE_PLUGIN_SLUG = 'akismet';

	/**
	 * Stylesheet of a theme that is always present in the test environment,
	 * used to simulate an "installed but not active" Woo theme subscription.
	 * Must differ from whatever theme is active when the test starts.
	 */
	private const INACTIVE_THEME_SLUG = 'storefront';

	/**
	 * Stylesheet of the theme active before each test, restored afterwards.
	 *
	 * @var string
	 */
	private $original_stylesheet;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		delete_transient( '_woocommerce_helper_subscriptions' );
		deactivate_plugins( self::INACTIVE_PLUGIN_SLUG . '/' . self::INACTIVE_PLUGIN_SLUG . '.php' );
		$this->original_stylesheet = get_stylesheet();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		delete_transient( '_woocommerce_helper_subscriptions' );
		deactivate_plugins( self::INACTIVE_PLUGIN_SLUG . '/' . self::INACTIVE_PLUGIN_SLUG . '.php' );
		switch_theme( $this->original_stylesheet );
		wp_set_current_user( 0 );
		remove_all_filters( 'wp_die_ajax_handler' );
		remove_all_filters( 'wp_doing_ajax' );
		parent::tearDown();
	}

	/**
	 * Stage a subscription for an installed-but-inactive theme. Switches to
	 * a different theme first so INACTIVE_THEME_SLUG is guaranteed inactive
	 * regardless of whatever theme the environment starts on.
	 *
	 * @return string The subscription's product_key.
	 */
	private function stage_inactive_theme_subscription(): string {
		switch_theme( 'twentytwentyfive' );

		$product_key = 'test-activate-key';
		set_transient(
			'_woocommerce_helper_subscriptions',
			array(
				array(
					'product_id'   => 999003,
					'product_key'  => $product_key,
					'product_type' => 'theme',
					'zip_slug'     => self::INACTIVE_THEME_SLUG,
					'connections'  => array(),
				),
			),
			HOUR_IN_SECONDS
		);
		return $product_key;
	}

	/**
	 * Stage a subscription for an installed-but-inactive plugin.
	 *
	 * @return string The subscription's product_key.
	 */
	private function stage_inactive_plugin_subscription(): string {
		$product_key = 'test-activate-key';
		set_transient(
			'_woocommerce_helper_subscriptions',
			array(
				array(
					'product_id'   => 999001,
					'product_key'  => $product_key,
					'product_type' => 'plugin',
					'zip_slug'     => self::INACTIVE_PLUGIN_SLUG,
					'connections'  => array(),
				),
			),
			HOUR_IN_SECONDS
		);
		return $product_key;
	}

	/**
	 * Invoke WC_Helper_Subscriptions_API::activate() and capture the JSON it
	 * would have sent, the same interception pattern SubmissionHandlerTest
	 * uses for wp_send_json_* callbacks that would otherwise call wp_die().
	 *
	 * @param string $product_key Product key to activate.
	 * @return array{success:bool, data:mixed, status:int}
	 */
	private function dispatch_activate( string $product_key ): array {
		$response = array(
			'success' => false,
			'data'    => null,
			'status'  => 200,
		);

		// wp_send_json_*() always terminates the request via wp_die(). Throwing
		// from the die handler stops execution at that exact point, the same
		// as a real request would — a no-op handler would instead let
		// activate() keep running past what should be a terminal response.
		add_filter(
			'wp_die_ajax_handler',
			static fn() => static function () {
				throw new RuntimeException( 'wp_die intercepted' );
			}
		);
		add_filter( 'wp_doing_ajax', static fn() => true );

		$request = new WP_REST_Request( 'POST', '/wc/v3/marketplace/subscriptions/activate' );
		$request->set_param( 'product_key', $product_key );

		ob_start();
		try {
			WC_Helper_Subscriptions_API::activate( $request );
		} catch ( RuntimeException $e ) {
			unset( $e );
		}
		$body = (string) ob_get_clean();

		$decoded = json_decode( $body, true );
		if ( is_array( $decoded ) ) {
			$response['success'] = ! empty( $decoded['success'] );
			$response['data']    = $decoded['data'] ?? null;
		}
		return $response;
	}

	/**
	 * @testdox A Shop Manager (manage_woocommerce but not activate_plugins) cannot activate a subscribed plugin.
	 */
	public function test_shop_manager_cannot_activate_plugin(): void {
		$product_key = $this->stage_inactive_plugin_subscription();

		$shop_manager_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		$this->assertFalse( user_can( $shop_manager_id, 'activate_plugins' ), 'Test precondition: shop_manager must not have activate_plugins.' );
		wp_set_current_user( $shop_manager_id );

		$response = $this->dispatch_activate( $product_key );

		$this->assertFalse( $response['success'] );
		$this->assertIsArray( $response['data'] );
		$this->assertStringContainsString( 'permission', $response['data']['message'] );
		$this->assertFalse( is_plugin_active( self::INACTIVE_PLUGIN_SLUG . '/' . self::INACTIVE_PLUGIN_SLUG . '.php' ) );
	}

	/**
	 * @testdox An Administrator (has activate_plugins) can still activate a subscribed plugin.
	 */
	public function test_administrator_can_activate_plugin(): void {
		$product_key = $this->stage_inactive_plugin_subscription();

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$response = $this->dispatch_activate( $product_key );

		$this->assertTrue( $response['success'] );
		$this->assertTrue( is_plugin_active( self::INACTIVE_PLUGIN_SLUG . '/' . self::INACTIVE_PLUGIN_SLUG . '.php' ) );
	}

	/**
	 * @testdox A Shop Manager (manage_woocommerce but not switch_themes) cannot activate a subscribed theme.
	 */
	public function test_shop_manager_cannot_activate_theme(): void {
		$product_key = $this->stage_inactive_theme_subscription();

		$shop_manager_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		$this->assertFalse( user_can( $shop_manager_id, 'switch_themes' ), 'Test precondition: shop_manager must not have switch_themes.' );
		wp_set_current_user( $shop_manager_id );

		$response = $this->dispatch_activate( $product_key );

		$this->assertFalse( $response['success'] );
		$this->assertIsArray( $response['data'] );
		$this->assertStringContainsString( 'permission', $response['data']['message'] );
		$this->assertNotSame( self::INACTIVE_THEME_SLUG, get_stylesheet() );
	}

	/**
	 * @testdox An Administrator (has switch_themes) can still activate a subscribed theme.
	 */
	public function test_administrator_can_activate_theme(): void {
		$product_key = $this->stage_inactive_theme_subscription();

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$response = $this->dispatch_activate( $product_key );

		$this->assertTrue( $response['success'] );
		$this->assertSame( self::INACTIVE_THEME_SLUG, get_stylesheet() );
	}

	/**
	 * @testdox A subscription with an unsupported product type cannot be activated, even by an Administrator.
	 */
	public function test_unsupported_product_type_is_rejected(): void {
		$product_key = 'test-activate-key';
		set_transient(
			'_woocommerce_helper_subscriptions',
			array(
				array(
					'product_id'   => 999002,
					'product_key'  => $product_key,
					'product_type' => 'unknown',
					'zip_slug'     => self::INACTIVE_PLUGIN_SLUG,
					'connections'  => array(),
				),
			),
			HOUR_IN_SECONDS
		);

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$response = $this->dispatch_activate( $product_key );

		$this->assertFalse( $response['success'] );
		$this->assertIsArray( $response['data'] );
		$this->assertStringContainsString( 'not supported', $response['data']['message'] );
	}
}
