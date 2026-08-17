<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Admin class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * WC_Admin_Test
 */
class WC_Admin_Test extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var WC_Admin
	 */
	private WC_Admin $sut;

	/**
	 * Original $_GET.
	 *
	 * @var array<string,mixed>
	 */
	private array $original_get = array();

	/**
	 * Original $_SERVER.
	 *
	 * @var array<string,mixed>
	 */
	private array $original_server = array();

	/**
	 * Original current user ID.
	 *
	 * @var int
	 */
	private int $original_current_user_id = 0;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut                      = new WC_Admin();
		$this->original_get             = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->original_server          = $_SERVER;
		$this->original_current_user_id = get_current_user_id();
		add_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );
		$_GET    = $this->original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_SERVER = $this->original_server;
		wp_set_current_user( $this->original_current_user_id );
		parent::tearDown();
	}

	/**
	 * Intercepts redirects so the tested handler's trailing exit does not run.
	 *
	 * @param string $location Redirect target.
	 * @return never
	 * @throws RuntimeException Always.
	 */
	public function intercept_redirect( string $location ): void {
		throw new RuntimeException( esc_url_raw( $location ) );
	}

	/**
	 * @testdox admin_redirects() only triggers the plugin install with a valid nonce for an allowed plugin slug.
	 */
	public function test_install_plugin_redirect_requires_valid_nonce(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Missing/invalid nonce: falls back to the search page.
		$_GET = array( // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'wc-install-plugin-redirect' => 'woocommerce-gateway-stripe',
			'_wpnonce'                   => 'not-a-valid-nonce',
		);
		try {
			$this->sut->admin_redirects();
			$this->fail( 'Expected the redirect interception to throw.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringNotContainsString( 'action=install-plugin', $e->getMessage(), 'Invalid nonce.' );
		}

		// Disallowed plugin slug, even with a matching valid nonce: also falls back.
		$_GET = array( // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'wc-install-plugin-redirect' => 'some-other-plugin',
			'_wpnonce'                   => wp_create_nonce( 'wc-install-plugin-redirect_some-other-plugin' ),
		);
		try {
			$this->sut->admin_redirects();
			$this->fail( 'Expected the redirect interception to throw.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringNotContainsString( 'action=install-plugin', $e->getMessage(), 'Disallowed plugin slug.' );
		}

		// Valid, matching nonce for the allowed plugin: triggers the install.
		$_GET = array( // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'wc-install-plugin-redirect' => 'woocommerce-gateway-stripe',
			'_wpnonce'                   => wp_create_nonce( 'wc-install-plugin-redirect_woocommerce-gateway-stripe' ),
		);
		try {
			$this->sut->admin_redirects();
			$this->fail( 'Expected the redirect interception to throw.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'action=install-plugin', $e->getMessage() );
			$this->assertStringContainsString( 'plugin=woocommerce-gateway-stripe', $e->getMessage() );
		}
	}

	/**
	 * @testdox Customers are redirected from non-exempt admin scripts.
	 * @dataProvider customer_non_exempt_admin_scripts_provider
	 *
	 * @param string              $script_filename Script filename.
	 * @param array<string,mixed> $get             Request query parameters.
	 */
	public function test_prevent_admin_access_redirects_customer_from_non_exempt_script( string $script_filename, array $get ): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );

		$this->assertSame(
			wc_get_page_permalink( 'myaccount' ),
			$this->invoke_prevent_admin_access( $customer_id, $script_filename, $get ),
			'Customers should be redirected to My Account from non-exempt admin scripts.'
		);
	}

	/**
	 * Provides customer request shapes that are not exempt from admin access prevention.
	 *
	 * @return array<string,array{string,array<string,mixed>}>
	 */
	public function customer_non_exempt_admin_scripts_provider(): array {
		return array(
			'customer ordinary wp-admin index script' => array( '/var/www/html/wp-admin/index.php', array() ),
			'customer wp-admin profile script'        => array( '/var/www/html/wp-admin/profile.php', array() ),
			'customer ordinary script with wc-ajax query parameter' => array( '/var/www/html/wp-admin/index.php', array( 'wc-ajax' => '1' ) ),
		);
	}

	/**
	 * @testdox Customers are not redirected from exempt or incomplete admin request shapes.
	 * @dataProvider customer_exempt_or_incomplete_admin_request_provider
	 *
	 * @param string|null $script_filename Script filename, if present.
	 */
	public function test_prevent_admin_access_does_not_redirect_customer_from_exempt_or_incomplete_request( ?string $script_filename ): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );

		$this->assertSame(
			null,
			$this->invoke_prevent_admin_access( $customer_id, $script_filename ),
			'Exempt or incomplete admin request shapes should not redirect customers.'
		);
	}

	/**
	 * Provides customer request shapes that are exempt from admin access prevention.
	 *
	 * @return array<string,array{string|null}>
	 */
	public function customer_exempt_or_incomplete_admin_request_provider(): array {
		return array(
			'customer admin-post script'              => array( '/var/www/html/wp-admin/admin-post.php' ),
			'customer admin-ajax script'              => array( '/var/www/html/wp-admin/admin-ajax.php' ),
			'customer missing SCRIPT_FILENAME server' => array( null ),
		);
	}

	/**
	 * @testdox Users with an admin access capability are not redirected.
	 * @dataProvider admin_access_capabilities_provider
	 *
	 * @param string $capability Capability granted to the user.
	 */
	public function test_prevent_admin_access_does_not_redirect_user_with_admin_access_capability( string $capability ): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		$user    = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User ) {
			throw new RuntimeException( 'Factory-created user could not be loaded.' );
		}
		$user->add_cap( $capability );

		$this->assertSame(
			null,
			$this->invoke_prevent_admin_access( $user_id, '/var/www/html/wp-admin/index.php' ),
			"Users granted {$capability} should not be redirected from an ordinary admin script."
		);
	}

	/**
	 * Provides capabilities that allow access to the admin.
	 *
	 * @return array<string,array{string}>
	 */
	public function admin_access_capabilities_provider(): array {
		return array(
			'user granted edit_posts'           => array( 'edit_posts' ),
			'user granted manage_woocommerce'   => array( 'manage_woocommerce' ),
			'user granted view_admin_dashboard' => array( 'view_admin_dashboard' ),
		);
	}

	/**
	 * @testdox The disable-admin-bar filter receives true and can suppress customer redirects.
	 */
	public function test_prevent_admin_access_disable_admin_bar_filter_can_suppress_customer_redirect(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		$received    = null;
		$callback    = static function ( $disabled ) use ( &$received ) {
			$received = $disabled;
			return false;
		};
		add_filter( 'woocommerce_disable_admin_bar', $callback );

		try {
			$this->assertSame( null, $this->invoke_prevent_admin_access( $customer_id, '/var/www/html/wp-admin/index.php' ), 'The disable-admin-bar filter should suppress the customer redirect.' );
			$this->assertSame( true, $received, 'The disable-admin-bar filter should receive its default true value.' );
		} finally {
			remove_filter( 'woocommerce_disable_admin_bar', $callback );
		}
	}

	/**
	 * @testdox The prevent-admin-access filter receives computed customer denial and can suppress it.
	 */
	public function test_prevent_admin_access_filter_can_suppress_computed_customer_denial(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		$received    = null;
		$callback    = static function ( $prevent_access ) use ( &$received ) {
			$received = $prevent_access;
			return false;
		};
		add_filter( 'woocommerce_prevent_admin_access', $callback );

		try {
			$this->assertSame( null, $this->invoke_prevent_admin_access( $customer_id, '/var/www/html/wp-admin/index.php' ), 'The prevent-admin-access filter should suppress the computed customer denial.' );
			$this->assertSame( true, $received, 'The prevent-admin-access filter should receive the computed customer denial.' );
		} finally {
			remove_filter( 'woocommerce_prevent_admin_access', $callback );
		}
	}

	/**
	 * @testdox The prevent-admin-access filter can force a redirect from an exempt request.
	 */
	public function test_prevent_admin_access_filter_can_force_redirect_from_exempt_request(): void {
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		$received    = null;
		$callback    = static function ( $prevent_access ) use ( &$received ) {
			$received = $prevent_access;
			return true;
		};
		add_filter( 'woocommerce_prevent_admin_access', $callback );

		try {
			$this->assertSame( wc_get_page_permalink( 'myaccount' ), $this->invoke_prevent_admin_access( $customer_id, '/var/www/html/wp-admin/admin-ajax.php' ), 'The prevent-admin-access filter should force an exact My Account redirect from an exempt request.' );
			$this->assertSame( false, $received, 'The prevent-admin-access filter should receive false for an exempt request.' );
		} finally {
			remove_filter( 'woocommerce_prevent_admin_access', $callback );
		}
	}

	/**
	 * Invokes prevent_admin_access() with a synthetic request and returns its redirect location.
	 *
	 * @param int                 $user_id         Current user ID.
	 * @param string|null         $script_filename Script filename, if present.
	 * @param array<string,mixed> $get             Request query parameters.
	 * @return string|null Redirect location, or null when no redirect occurred.
	 */
	private function invoke_prevent_admin_access( int $user_id, ?string $script_filename, array $get = array() ): ?string {
		wp_set_current_user( $user_id );
		$_GET = $get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( null === $script_filename ) {
			unset( $_SERVER['SCRIPT_FILENAME'] );
		} else {
			$_SERVER['SCRIPT_FILENAME'] = $script_filename;
		}

		try {
			$this->sut->prevent_admin_access();
		} catch ( RuntimeException $e ) {
			return $e->getMessage();
		}

		return null;
	}
}
