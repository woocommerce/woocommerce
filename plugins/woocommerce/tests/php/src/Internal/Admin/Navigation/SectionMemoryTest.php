<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Menu_Reconciler;
use Automattic\WooCommerce\Internal\Admin\Navigation\Section_Memory;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Section_Memory
 */
class SectionMemoryTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Section_Memory
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new Section_Memory();
		unset(
			$_COOKIE[ Section_Memory::COOKIE_NAME ],
			$_SERVER['HTTP_REFERER'],
			$_REQUEST['_wp_http_referer']
		);
	}

	public function tearDown(): void {
		// Section_Memory registers an admin_init hook on construction; remove
		// only ours so it doesn't leak into other suites.
		remove_action( 'admin_init', array( $this->sut, 'sync_section' ), 1 );
		remove_all_filters( 'wp_redirect' );
		$this->set_tree( null );
		unset(
			$_COOKIE[ Section_Memory::COOKIE_NAME ],
			$_SERVER['HTTP_REFERER'],
			$_REQUEST['_wp_http_referer'],
			$GLOBALS['pagenow']
		);
		parent::tearDown();
	}

	/**
	 * Invoke a private Section_Memory method.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments.
	 * @return mixed
	 */
	private function invoke( string $method, array $args = array() ) {
		$ref = new \ReflectionMethod( Section_Memory::class, $method );
		$ref->setAccessible( true );
		return $ref->invoke( $this->sut, ...$args );
	}

	/**
	 * Set the reconciler's static tree, which sync_section reads via get_tree().
	 *
	 * @param array|null $tree Tree, or null to clear.
	 */
	private function set_tree( ?array $tree ): void {
		$ref = new \ReflectionProperty( Menu_Reconciler::class, 'tree' );
		$ref->setAccessible( true );
		$ref->setValue( null, $tree );
	}

	/**
	 * A tampered cookie pointing outside wp-admin is rejected, guarding against
	 * a cookie-driven open redirect.
	 */
	public function test_read_cookie_target_rejects_path_outside_admin() {
		$_COOKIE[ Section_Memory::COOKIE_NAME ] = rawurlencode( '/evil/elsewhere' );

		$this->assertNull( $this->invoke( 'read_cookie_target' ) );
	}

	/**
	 * A cookie whose decoded path is scoped under the admin path is accepted
	 * and returned verbatim.
	 */
	public function test_read_cookie_target_returns_stored_admin_path() {
		$target = $this->invoke( 'cookie_path' ) . 'admin.php?page=wc-admin';
		$_COOKIE[ Section_Memory::COOKIE_NAME ] = rawurlencode( $target );

		$this->assertSame( $target, $this->invoke( 'read_cookie_target' ) );
	}

	/**
	 * No cookie means no stored target.
	 */
	public function test_read_cookie_target_returns_null_when_unset() {
		$this->assertNull( $this->invoke( 'read_cookie_target' ) );
	}

	/**
	 * Arriving with no referer (login redirect, typed URL, external link) is a
	 * fresh entry.
	 */
	public function test_is_fresh_entry_true_without_referer() {
		$this->assertTrue( $this->invoke( 'is_fresh_entry' ) );
	}

	/**
	 * A click that originated inside wp-admin is intentional navigation, not a
	 * fresh entry, so the bounce-to-remembered-URL must not fire.
	 */
	public function test_is_fresh_entry_false_for_in_admin_referer() {
		$_SERVER['HTTP_REFERER'] = admin_url( 'edit.php' );

		$this->assertFalse( $this->invoke( 'is_fresh_entry' ) );
	}

	/**
	 * A fresh arrival at the Dashboard root with a remembered Woo URL is
	 * redirected to that URL.
	 */
	public function test_dashboard_entry_redirects_to_remembered_woo_url() {
		$this->set_tree(
			array(
				'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
				'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10 ),
			)
		);
		$GLOBALS['pagenow'] = 'index.php';
		$target             = $this->invoke( 'cookie_path' ) . 'admin.php?page=wc-admin';
		$_COOKIE[ Section_Memory::COOKIE_NAME ] = rawurlencode( $target );

		// Intercept the redirect so the production `exit` never runs. The
		// location is captured for assertion; the thrown message is static so
		// it needs no escaping.
		$captured = null;
		add_filter(
			'wp_redirect',
			static function ( $location ) use ( &$captured ) {
				$captured = $location;
				throw new \RuntimeException( 'nav-v2 test: redirect intercepted' );
			}
		);

		try {
			$this->sut->sync_section();
			$this->fail( 'Expected a redirect to the remembered Woo URL.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'redirect intercepted', $e->getMessage() );
		}

		$this->assertNotNull( $captured, 'A redirect should have been issued.' );
		$this->assertStringContainsString( 'admin.php?page=wc-admin', (string) $captured );
	}
}
