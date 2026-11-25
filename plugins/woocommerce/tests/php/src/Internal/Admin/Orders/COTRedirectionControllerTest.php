<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\COTRedirectionController;

/**
 * Describes our redirection logic covering HPOS admin screens when Custom Order Tables are not authoritative.
 */
class COTRedirectionControllerTest extends \WC_Unit_Test_Case {
	/**
	 * @var COTRedirectionController
	 */
	private $sut;

	/**
	 * Holds the URL of the last attempted redirect.
	 *
	 * @var string
	 */
	private $redirected_to = '';

	/**
	 * Setup our SUT and start listening for redirects.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new COTRedirectionController();
		$this->sut->setup();
		$this->redirected_to = '';

		add_filter( 'wp_redirect', array( $this, 'watch_and_annul_redirects' ) );
	}

	/**
	 * Captures the attempted redirect location, and stops the redirect from taking place.
	 *
	 * @param string $url Redirect location.
	 *
	 * @return null
	 */
	public function watch_and_annul_redirects( string $url ) {
		$this->redirected_to = $url;
		return null;
	}

	/**
	 * Supplies the URL of the last attempted redirect, then resets ready for the next test.
	 *
	 * @return string
	 */
	private function get_redirect_attempt(): string {
		$return              = $this->redirected_to;
		$this->redirected_to = '';
		return $return;
	}

	/**
	 * @testdox Redirects HPOS requests from both missing-page and access-denied hooks, leaving unrelated requests unchanged.
	 *
	 * @dataProvider provide_admin_request_redirects
	 * @param string $hook The admin request hook.
	 * @param array  $query_params The request query parameters.
	 * @param string $expected_path The expected redirect path, or an empty string for no redirect.
	 */
	public function test_redirects_from_admin_request_hooks( string $hook, array $query_params, string $expected_path ): void {
		$original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the request while simulating admin hooks.
		$_GET         = $query_params;

		try {
			do_action( $hook );

			$this->assertSame(
				'' === $expected_path ? '' : get_admin_url( null, $expected_path ),
				$this->get_redirect_attempt(),
				'Admin request hooks should redirect only HPOS order requests to the equivalent posts screens.'
			);
		} finally {
			$_GET = $original_get;
		}
	}

	/**
	 * Provides requests for both current and legacy WordPress admin hooks.
	 *
	 * @return array
	 */
	public function provide_admin_request_redirects(): array {
		$requests = array(
			'order list'   => array(
				array( 'page' => 'wc-orders' ),
				'edit.php?post_type=shop_order',
			),
			'order editor' => array(
				array(
					'page'   => 'wc-orders',
					'action' => 'edit',
					'id'     => '123',
				),
				'post.php?action=edit&post=123',
			),
			'new order'    => array(
				array(
					'page'   => 'wc-orders',
					'action' => 'new',
				),
				'post-new.php?post_type=shop_order',
			),
			'other page'   => array( array( 'page' => 'foo' ), '' ),
			'no page'      => array( array(), '' ),
		);
		$cases    = array();

		foreach ( array( 'admin_page_access_denied', 'wp_admin_page_not_found' ) as $hook ) {
			foreach ( $requests as $name => $request ) {
				$cases[ $hook . ': ' . $name ] = array( $hook, $request[0], $request[1] );
			}
		}

		return $cases;
	}

	/**
	 * Test that redirects only occur in relation to HPOS admin screen requests.
	 *
	 * @return void
	 */
	public function test_redirects_only_impact_hpos_admin_requests() {
		$this->sut->handle_hpos_admin_requests( array( 'page' => 'wc-orders' ) );
		$this->assertNotEmpty( $this->get_redirect_attempt(), 'A redirect was attempted in relation to an HPOS admin request.' );

		$this->sut->handle_hpos_admin_requests( array( 'page' => 'foo' ) );
		$this->assertEmpty( $this->get_redirect_attempt(), 'A redirect was not attempted in relation to a non-HPOS admin request.' );
	}

	/**
	 * Test order editor redirects work (in relation to creating new orders).
	 *
	 * @return void
	 */
	public function test_redirects_to_the_new_order_screen(): void {
		$this->sut->handle_hpos_admin_requests(
			array(
				'action' => 'new',
				'page'   => 'wc-orders',
			)
		);

		$this->assertStringContainsString(
			'/wp-admin/post-new.php?post_type=shop_order',
			$this->get_redirect_attempt(),
			'Attempts to access the new order page (HPOS) are successfully redirected to the new order page (CPT).'
		);
	}

	/**
	 * Test order editor redirects work (in relation to existing orders).
	 *
	 * @return void
	 */
	public function test_redirects_to_the_order_editor_screen(): void {
		$this->sut->handle_hpos_admin_requests(
			array(
				'action' => 'edit',
				'id'     => 12345,
				'page'   => 'wc-orders',
			)
		);

		$redirect_url  = $this->get_redirect_attempt();
		$redirect_base = wp_parse_url( $redirect_url, PHP_URL_PATH );
		parse_str( wp_parse_url( $redirect_url, PHP_URL_QUERY ), $redirect_query );

		$this->assertStringContainsString(
			'/post.php',
			$redirect_base,
			'Confirm order editor redirects go to the expected WordPress admin controller.'
		);

		$this->assertEquals(
			'12345',
			$redirect_query['post'],
			'Confirm order editor redirects maintain the correct order ID.'
		);
	}

	/**
	 * Tests order list table redirects work.
	 *
	 * @return void
	 */
	public function test_redirects_to_the_order_admin_list_screen(): void {
		$this->sut->handle_hpos_admin_requests(
			array(
				'arbitrary' => '3pd-integration',
				'id'        => array(
					123,
					456,
				),
				'page'      => 'wc-orders',
			)
		);

		$redirect_url  = $this->get_redirect_attempt();
		$redirect_base = wp_parse_url( $redirect_url, PHP_URL_PATH );
		parse_str( wp_parse_url( $redirect_url, PHP_URL_QUERY ), $redirect_query );

		$this->assertStringContainsString(
			'/edit.php',
			$redirect_base,
			'Confirm order list table redirects go to the expected WordPress admin controller.'
		);

		$this->assertEquals(
			array(
				'123',
				'456',
			),
			$redirect_query['post'],
			'Confirm order list table redirects maintain a list of order IDs for bulk action requests (if one was passed).'
		);

		$this->assertEquals(
			'shop_order',
			$redirect_query['post_type'],
			'Confirm order list table redirects reference the correct custom post type.'
		);

		$this->assertEquals(
			'3pd-integration',
			$redirect_query['arbitrary'],
			'Confirm that arbitrary query parameters are also passed across via order list table redirects.'
		);
	}
}
