<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Jetpack;

use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;
use Automattic\WooCommerce\Tests\Helpers\JetpackConnectionManagerTrait;
use WC_Unit_Test_Case;

/**
 * Tests for the JetpackConnection class.
 */
class JetpackConnectionTest extends WC_Unit_Test_Case {
	use JetpackConnectionManagerTrait;

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			$this->reset_jetpack_connection_manager();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should force registration of an unconnected site when asked to.
	 */
	public function test_get_authorization_url_forces_registration_when_asked(): void {
		$calls = $this->use_recording_jetpack_connection_manager();

		JetpackConnection::get_authorization_url( 'https://example.com/wp-admin/', 'woocommerce-core-profiler', true );

		$this->assertSame( array( array( true, true ) ), $calls->getArrayCopy(), 'Registration should be attempted once, forced.' );
	}

	/**
	 * @testdox Should not force registration when no force flag is given.
	 */
	public function test_get_authorization_url_does_not_force_by_default(): void {
		$calls = $this->use_recording_jetpack_connection_manager();

		JetpackConnection::get_authorization_url( 'https://example.com/wp-admin/' );

		$this->assertSame( array( array( true, false ) ), $calls->getArrayCopy(), 'Callers that do not ask for it should keep the connection package backoff.' );
	}

	/**
	 * @testdox Should not attempt registration when the site is already connected.
	 */
	public function test_get_authorization_url_skips_registration_when_connected(): void {
		$calls = $this->use_recording_jetpack_connection_manager( true );

		JetpackConnection::get_authorization_url( 'https://example.com/wp-admin/', '', true );

		$this->assertSame( array(), $calls->getArrayCopy(), 'A connected site should not register again.' );
	}

	/**
	 * @testdox Should report a registration error and keep returning the authorization URL.
	 */
	public function test_get_authorization_url_reports_registration_errors(): void {
		$this->use_recording_jetpack_connection_manager( false, new \WP_Error( 'wpcom_5??', 'Registration failed.' ) );

		$result = JetpackConnection::get_authorization_url( 'https://example.com/wp-admin/', '', true );

		$this->assertFalse( $result['success'], 'A failed registration should not report success.' );
		$this->assertSame( array( 'Registration failed.' ), $result['errors'], 'The registration error message should be returned.' );
		$this->assertStringStartsWith( 'https://jetpack.wordpress.com/jetpack.authorize/1/', $result['url'], 'The authorization URL should still be built.' );
	}
}
