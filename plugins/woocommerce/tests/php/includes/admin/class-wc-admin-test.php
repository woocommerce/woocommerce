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
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut          = new WC_Admin();
		$this->original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		add_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );
		$_GET = $this->original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_set_current_user( 0 );
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
}
