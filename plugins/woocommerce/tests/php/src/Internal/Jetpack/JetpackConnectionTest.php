<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Jetpack;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;

/**
 * Tests for the JetpackConnection class.
 */
class JetpackConnectionTest extends WC_Unit_Test_Case {

	/**
	 * Arguments of every try_registration() call made on the mocked manager.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $registration_calls = array();

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			// JetpackConnection caches its manager in a static property that no base class resets.
			$this->set_manager( null );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should register an unconnected site, passing the requested force flag to the connection package.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $force Whether the merchant asked to connect.
	 */
	public function test_get_authorization_url_passes_force_to_registration( bool $force ): void {
		$this->set_manager( $this->create_manager( false ) );

		JetpackConnection::get_authorization_url( 'https://example.com/wp-admin/', 'woocommerce-core-profiler', $force );

		$this->assertSame( array( array( true, $force ) ), $this->registration_calls, 'Registration should be attempted once, with the force flag passed through.' );
	}

	/**
	 * @testdox Should not force registration when no force flag is given.
	 */
	public function test_get_authorization_url_does_not_force_by_default(): void {
		$this->set_manager( $this->create_manager( false ) );

		JetpackConnection::get_authorization_url( 'https://example.com/wp-admin/' );

		$this->assertSame( array( array( true, false ) ), $this->registration_calls, 'Callers that do not ask for it should keep the connection package backoff.' );
	}

	/**
	 * @testdox Should not attempt registration when the site is already connected.
	 */
	public function test_get_authorization_url_skips_registration_when_connected(): void {
		$this->set_manager( $this->create_manager( true ) );

		JetpackConnection::get_authorization_url( 'https://example.com/wp-admin/', '', true );

		$this->assertSame( array(), $this->registration_calls, 'A connected site should not register again.' );
	}

	/**
	 * @testdox Should report a registration error and keep returning the authorization URL.
	 */
	public function test_get_authorization_url_reports_registration_errors(): void {
		$manager = $this->create_manager( false, new \WP_Error( 'wpcom_5??', 'Registration failed.' ) );
		$this->set_manager( $manager );

		$result = JetpackConnection::get_authorization_url( 'https://example.com/wp-admin/', '', true );

		$this->assertFalse( $result['success'], 'A failed registration should not report success.' );
		$this->assertSame( array( 'Registration failed.' ), $result['errors'], 'The registration error message should be returned.' );
		$this->assertStringStartsWith( 'https://jetpack.wordpress.com/jetpack.authorize/1/', $result['url'], 'The authorization URL should still be built.' );
	}

	/**
	 * Create a connection manager mock that records try_registration() calls.
	 *
	 * @param bool           $is_connected        Whether the site is already registered.
	 * @param true|\WP_Error $registration_result What try_registration() returns.
	 * @return Manager&MockObject
	 */
	private function create_manager( bool $is_connected, $registration_result = true ) {
		$manager = $this->createMock( Manager::class );
		$manager->method( 'is_connected' )->willReturn( $is_connected );
		$manager->method( 'get_authorization_url' )->willReturn( 'https://jetpack.wordpress.com/jetpack.authorize/1/?client_id=1' );
		$manager->method( 'try_registration' )->willReturnCallback(
			function ( ...$args ) use ( $registration_result ) {
				$this->registration_calls[] = $args;
				return $registration_result;
			}
		);

		return $manager;
	}

	/**
	 * Replace the manager JetpackConnection caches.
	 *
	 * @param Manager|null $manager Manager to use, or null to reset.
	 */
	private function set_manager( $manager ): void {
		$property = new \ReflectionProperty( JetpackConnection::class, 'manager' );
		$property->setAccessible( true );
		$property->setValue( null, $manager );
	}
}
