<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain;

use Automattic\WooCommerce\Blocks\Domain\BlockRegistrationContext;
use WC_Unit_Test_Case;

/**
 * Unit tests for the BlockRegistrationContext class.
 */
class BlockRegistrationContextTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var BlockRegistrationContext
	 */
	private BlockRegistrationContext $sut;

	/**
	 * The original REQUEST_URI, restored after each test.
	 *
	 * @var string
	 */
	private string $original_uri;

	/**
	 * The original $pagenow, restored after each test.
	 *
	 * @var string
	 */
	private string $original_pagenow;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut              = new BlockRegistrationContext();
		$this->original_uri     = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this->original_pagenow = $GLOBALS['pagenow'] ?? '';
	}

	/**
	 * Restore the request globals after each test.
	 */
	public function tearDown(): void {
		$_SERVER['REQUEST_URI'] = $this->original_uri;
		$GLOBALS['pagenow']     = $this->original_pagenow; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		unset( $_GET['rest_route'], $_GET['wc-ajax'], $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		set_current_screen( 'front' );
		parent::tearDown();
	}

	/**
	 * Simulate an admin page request: make is_admin() true and set $pagenow / the page query var.
	 *
	 * @param string $screen_id The admin screen id to set as current.
	 * @param string $pagenow   The $pagenow value to simulate.
	 * @param string $page      The ?page= query value to simulate (empty to leave unset).
	 */
	private function simulate_admin_page( string $screen_id, string $pagenow, string $page = '' ): void {
		set_current_screen( $screen_id );
		$GLOBALS['pagenow'] = $pagenow; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( '' !== $page ) {
			$_GET['page'] = $page;
		}
	}

	/**
	 * @testdox Should register blocks only for requests that may render or edit them.
	 * @dataProvider request_provider
	 *
	 * @param string      $uri        The REQUEST_URI to simulate.
	 * @param string|null $rest_route The ?rest_route= query value to simulate, if any.
	 * @param string|null $wc_ajax    The ?wc-ajax= query value to simulate, if any.
	 * @param bool        $expected   Whether blocks should be registered.
	 */
	public function test_should_register( string $uri, ?string $rest_route, ?string $wc_ajax, bool $expected ): void {
		$_SERVER['REQUEST_URI'] = $uri;
		unset( $_GET['rest_route'], $_GET['wc-ajax'] );
		if ( null !== $rest_route ) {
			$_GET['rest_route'] = $rest_route;
		}
		if ( null !== $wc_ajax ) {
			$_GET['wc-ajax'] = $wc_ajax;
		}

		$this->assertSame(
			$expected,
			$this->sut->should_register(),
			sprintf( 'Unexpected should_register() result for "%s".', $uri )
		);
	}

	/**
	 * Data provider for test_should_register.
	 *
	 * @return array<string, array{0:string,1:?string,2:?string,3:bool}>
	 */
	public function request_provider(): array {
		return array(
			// label                       => array( uri, rest_route, wc-ajax, expected ).
			'front-end home'                => array( '/', null, null, true ),
			'front-end shop page'           => array( '/shop/', null, null, true ),
			'front-end page named sitemap'  => array( '/wp-sitemap-guide/', null, null, true ),

			'store api (pretty)'            => array( '/wp-json/wc/store/v1/cart', null, null, false ),
			'store api (plain)'             => array( '/index.php?rest_route=/wc/store/v1/cart', '/wc/store/v1/cart', null, false ),

			'wc/v3 rest (pretty)'           => array( '/wp-json/wc/v3/orders', null, null, false ),
			'wc/v4 rest (pretty)'           => array( '/wp-json/wc/v4/products', null, null, false ),
			'wc/v3 rest (plain)'            => array( '/index.php?rest_route=/wc/v3/orders', '/wc/v3/orders', null, false ),

			// Repeated leading slashes must still be recognised (some servers send them; WP trims request URIs too).
			'wc/v3 rest (leading slashes)'  => array( '///wp-json/wc/v3/orders', null, null, false ),
			'wc/v3 plain (leading slashes)' => array( '/index.php?rest_route=//wc/v3/orders', '//wc/v3/orders', null, false ),
			'wc-admin rest'                 => array( '/wp-json/wc-admin/options', null, null, false ),
			'wc-analytics rest'             => array( '/wp-json/wc-analytics/reports', null, null, false ),
			'wc-telemetry rest'             => array( '/wp-json/wc-telemetry/tracker', null, null, false ),
			'wc/private rest (pretty)'      => array( '/wp-json/wc/private/v1/x', null, null, false ),
			'wc/private rest (plain)'       => array( '/index.php?rest_route=/wc/private/v1/x', '/wc/private/v1/x', null, false ),

			'wc/vendor not a version'       => array( '/wp-json/wc/vendor/x', null, null, true ),
			'wc/voucher plain not version'  => array( '/index.php?rest_route=/wc/voucher/x', '/wc/voucher/x', null, true ),

			// A REST-like namespace in a query argument must not be mistaken for a REST request; only the path counts.
			'rest-like arg in query'        => array( '/some-page/?arg=/wp-json/wc/v3', null, null, true ),

			// wp/v2 is WordPress core's namespace, which the block and site editors rely on, so the whole
			// namespace keeps registering (true) — even endpoints like users/me that render no blocks.
			'wp/v2 editor rest'             => array( '/wp-json/wp/v2/types', null, null, true ),
			'wp/v2 users/me'                => array( '/wp-json/wp/v2/users/me', null, null, true ),

			'favicon'                       => array( '/favicon.ico', null, null, false ),
			'favicon (subdirectory)'        => array( '/blog/favicon.ico', null, null, false ),
			'favicon (repeated slash)'      => array( '///favicon.ico', null, null, false ),
			'robots.txt'                    => array( '/robots.txt', null, null, false ),
			'sitemap index'                 => array( '/wp-sitemap.xml', null, null, false ),
			'sitemap posts'                 => array( '/wp-sitemap-posts-post-1.xml', null, null, false ),
			'sitemap stylesheet'            => array( '/wp-sitemap.xsl', null, null, false ),

			'wc-ajax (add to cart)'         => array( '/?wc-ajax=add_to_cart', null, 'add_to_cart', false ),
			'wc-ajax (empty value)'         => array( '/?wc-ajax=', null, '', true ),
		);
	}

	/**
	 * @testdox Should not register on a WooCommerce admin page (admin.php?page=wc-*).
	 * @dataProvider woocommerce_admin_page_provider
	 *
	 * @param string $page The ?page= query value for the WooCommerce admin screen.
	 */
	public function test_should_not_register_on_woocommerce_admin_page( string $page ): void {
		$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=' . $page;
		$this->simulate_admin_page( 'woocommerce_page_' . $page, 'admin.php', $page );

		$this->assertFalse(
			$this->sut->should_register(),
			sprintf( 'A WooCommerce admin page (page=%s) should not register blocks.', $page )
		);
	}

	/**
	 * Data provider for WooCommerce admin pages.
	 *
	 * @return array<string, array{0:string}>
	 */
	public function woocommerce_admin_page_provider(): array {
		return array(
			'wc-admin'    => array( 'wc-admin' ),
			'wc-settings' => array( 'wc-settings' ),
			'wc-orders'   => array( 'wc-orders' ),
			'wc-reports'  => array( 'wc-reports' ),
			'wc-status'   => array( 'wc-status' ),
			'wc-addons'   => array( 'wc-addons' ),
		);
	}

	/**
	 * @testdox Should still register on a non-WooCommerce admin page.
	 */
	public function test_should_register_on_non_woocommerce_admin_page(): void {
		$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=some-other-plugin';
		$this->simulate_admin_page( 'toplevel_page_some-other-plugin', 'admin.php', 'some-other-plugin' );

		$this->assertTrue(
			$this->sut->should_register(),
			'A non-WooCommerce admin page should still register blocks.'
		);
	}

	/**
	 * @testdox Should still register on the block editor screen.
	 */
	public function test_should_register_on_block_editor_screen(): void {
		$_SERVER['REQUEST_URI'] = '/wp-admin/post-new.php';
		$this->simulate_admin_page( 'post', 'post-new.php' );

		$this->assertTrue(
			$this->sut->should_register(),
			'The block editor screen should still register blocks.'
		);
	}
}
