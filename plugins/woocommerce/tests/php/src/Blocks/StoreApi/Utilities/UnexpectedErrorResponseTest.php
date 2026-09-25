<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\StoreApi\Utilities\UnexpectedErrorResponse;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Logger_Interface;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the UnexpectedErrorResponse class.
 */
class UnexpectedErrorResponseTest extends WC_Unit_Test_Case {
	use LoggerSpyTrait;

	/**
	 * Set up the current user.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( 0 );
		Constants::set_constant( 'WP_DEBUG', false );
	}

	/**
	 * Restore the current user.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		Constants::clear_single_constant( 'WP_DEBUG' );
		parent::tearDown();
	}

	/**
	 * @testdox Should disclose engine failure details only to managers in debug mode.
	 * @testWith [null, false, false]
	 *           ["customer", false, false]
	 *           [null, true, false]
	 *           ["administrator", false, false]
	 *           ["administrator", true, true]
	 *
	 * @param string|null $role                Current user's role, or null for a guest.
	 * @param bool        $debug_enabled       Whether debug mode is enabled.
	 * @param bool        $should_show_details Whether error details should be disclosed.
	 */
	public function test_discloses_engine_failure_details_only_to_managers_in_debug_mode( ?string $role, bool $debug_enabled, bool $should_show_details ): void {
		Constants::set_constant( 'WP_DEBUG', $debug_enabled );
		if ( null !== $role ) {
			$this->login_as_role( $role );
		}

		$result = UnexpectedErrorResponse::create( new \TypeError( 'Fixture engine failure.' ), self::class );

		$this->assert_error_response( $result, $should_show_details, 'Engine failure disclosure should require both debug mode and manager capability.' );
	}

	/**
	 * @testdox Should log the engine failure as critical with a bounded backtrace.
	 */
	public function test_logs_engine_failure_as_critical_with_bounded_backtrace(): void {
		$error = new \TypeError( 'Fixture logged engine failure.' );

		UnexpectedErrorResponse::create( $error, self::class );

		$this->assertLogged(
			'critical',
			self::class,
			array(
				'source'    => 'store-api',
				'exception' => $error,
			)
		);
		$this->assertCount( 1, $this->captured_logs );
		$message = $this->captured_logs[0]['message'];
		$this->assertStringContainsString( \TypeError::class, $message );
		$this->assertStringContainsString( 'Fixture logged engine failure.', $message );

		$backtrace = $this->captured_logs[0]['context']['backtrace'];
		$this->assertIsArray( $backtrace );
		$this->assertGreaterThanOrEqual( 1, count( $backtrace ) );
		$this->assertLessThanOrEqual( 10, count( $backtrace ) );
		$this->assertStringStartsWith( '#0 ', $backtrace[0] );
	}

	/**
	 * @testdox Should return a safe response when logging fails.
	 */
	public function test_returns_safe_response_when_logging_fails(): void {
		$logger = $this->createMock( WC_Logger_Interface::class );
		$logger
			->method( 'critical' )
			->willThrowException( new \RuntimeException( 'Fixture logger failure.' ) );
		add_filter( 'woocommerce_logging_class', fn() => $logger );

		$result = UnexpectedErrorResponse::create( new \TypeError( 'Fixture engine failure.' ), self::class );

		$this->assert_error_response( $result, false, 'A logging failure must not change the safe response.' );
	}

	/**
	 * @testdox Should let the disclosure filter override debug mode but never the capability check.
	 * @testWith ["administrator", false, true, true]
	 *           ["customer", false, true, false]
	 *           ["administrator", true, false, false]
	 *
	 * @param string $role                Current user's role.
	 * @param bool   $debug_enabled       Whether debug mode is enabled.
	 * @param bool   $filter_value        Value returned by the disclosure filter.
	 * @param bool   $should_show_details Whether error details should be disclosed.
	 */
	public function test_filter_overrides_debug_mode_but_not_capability( string $role, bool $debug_enabled, bool $filter_value, bool $should_show_details ): void {
		Constants::set_constant( 'WP_DEBUG', $debug_enabled );
		$this->login_as_role( $role );

		$received_default = null;
		add_filter(
			'woocommerce_store_api_expose_error_details',
			static function ( $expose ) use ( $filter_value, &$received_default ) {
				$received_default = $expose;
				return $filter_value;
			}
		);

		$result = UnexpectedErrorResponse::create( new \TypeError( 'Fixture engine failure.' ), self::class );

		$this->assertSame( $debug_enabled, $received_default, 'The filter default must be the debug mode state.' );
		$this->assert_error_response( $result, $should_show_details, 'Disclosure must require the capability even when the filter allows it.' );
	}

	/**
	 * Assert the error response for a fixture engine failure, masked or disclosed.
	 *
	 * @param WP_Error $result          Response created for the fixture failure.
	 * @param bool     $details_shown   Whether the message and exception class should be disclosed.
	 * @param string   $failure_message Assertion message.
	 */
	private function assert_error_response( WP_Error $result, bool $details_shown, string $failure_message ): void {
		$expected_message = $details_shown ? 'Fixture engine failure.' : __( 'Internal server error', 'woocommerce' );
		$expected_data    = array( 'status' => 500 );
		if ( $details_shown ) {
			$expected_data['exception_class'] = \TypeError::class;
		}

		$this->assertSame( 'woocommerce_rest_unknown_server_error', $result->get_error_code(), 'Engine failures should use the established error code.' );
		$this->assertSame( $expected_message, $result->get_error_message(), $failure_message );
		$this->assertSame( $expected_data, $result->get_error_data(), 'Response data must follow the same gate as the message.' );
	}
}
