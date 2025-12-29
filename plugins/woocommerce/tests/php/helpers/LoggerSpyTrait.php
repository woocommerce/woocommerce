<?php
/**
 * LoggerSpyTrait file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\RestApi\UnitTests;

/**
 * Trait LoggerSpyTrait.
 *
 * Provides a spy logger that captures all log calls for later assertions.
 * Uses PHPUnit mock with a callback to capture logs without constraining calls.
 */
trait LoggerSpyTrait {

	/**
	 * Captured log entries.
	 *
	 * @var array<array{level: string, message: string, context: array}>
	 */
	private array $captured_logs = array();

	/**
	 * The mock logger instance.
	 *
	 * @var \WC_Logger_Interface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $spy_logger;

	/**
	 * Set up the spy logger.
	 *
	 * This method is called automatically before each test via the @before annotation.
	 *
	 * @before
	 * @return void
	 */
	protected function set_up_spy_logger(): void {
		$this->captured_logs = array();
		$this->spy_logger    = $this->getMockBuilder( \WC_Logger_Interface::class )->getMock();

		$this->spy_logger->method( 'log' )->willReturnCallback(
			function ( $level, $message, $context = array() ) {
				$this->captured_logs[] = array(
					'level'   => $level,
					'message' => $message,
					'context' => $context,
				);
			}
		);

		add_filter( 'woocommerce_logging_class', array( $this, 'get_spy_logger' ) );
	}

	/**
	 * Tear down the spy logger.
	 *
	 * This method is called automatically after each test via the @after annotation.
	 *
	 * @after
	 * @return void
	 */
	protected function tear_down_spy_logger(): void {
		remove_filter( 'woocommerce_logging_class', array( $this, 'get_spy_logger' ) );
		$this->captured_logs = array();
	}

	/**
	 * Get the spy logger instance.
	 *
	 * @return \WC_Logger_Interface|\PHPUnit\Framework\MockObject\MockObject
	 */
	public function get_spy_logger() {
		return $this->spy_logger;
	}

	/**
	 * Assert that a log message was recorded at the given level containing the substring.
	 *
	 * @param string     $level            The log level to check (e.g., 'error', 'info', 'warning', 'debug').
	 * @param string     $substring        The substring to search for in messages.
	 * @param array|null $expected_context Optional expected context structure to match.
	 * @return void
	 */
	protected function assertLogged( string $level, string $substring, ?array $expected_context = null ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- PHPUnit style.
		$logs = $this->get_logs_by_level( $level );

		foreach ( $logs as $log ) {
			if ( str_contains( $log['message'], $substring ) ) {
				if ( null !== $expected_context && ! $this->context_matches( $expected_context, $log['context'] ) ) {
					continue;
				}
				$this->addToAssertionCount( 1 );
				return;
			}
		}

		$context_info = null !== $expected_context ? sprintf( ' with context %s', wp_json_encode( $expected_context ) ) : '';
		$this->fail(
			sprintf(
				"Expected %s log containing '%s'%s.\nLogged %s entries: %s",
				$level,
				$substring,
				$context_info,
				$level,
				wp_json_encode( $logs, JSON_PRETTY_PRINT )
			)
		);
	}

	/**
	 * Assert that no error was logged.
	 *
	 * @return void
	 */
	protected function assertNoErrorLogged(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- PHPUnit style.
		$errors = $this->get_logs_by_level( 'error' );
		$this->assertEmpty(
			$errors,
			sprintf(
				'Expected no error logs, but found: %s',
				wp_json_encode( $errors, JSON_PRETTY_PRINT )
			)
		);
	}

	/**
	 * Get captured logs filtered by level.
	 *
	 * @param string $level Log level to filter by.
	 * @return array<array{level: string, message: string, context: array}>
	 */
	private function get_logs_by_level( string $level ): array {
		return array_filter(
			$this->captured_logs,
			fn( $log ) => $log['level'] === $level
		);
	}

	/**
	 * Check if the expected context is a subset of the actual context.
	 *
	 * @param array $expected The expected context structure.
	 * @param array $actual   The actual logged context.
	 * @return bool True if all expected keys/values are found in actual.
	 */
	private function context_matches( array $expected, array $actual ): bool {
		foreach ( $expected as $key => $value ) {
			if ( ! array_key_exists( $key, $actual ) ) {
				return false;
			}

			if ( is_array( $value ) && is_array( $actual[ $key ] ) ) {
				if ( ! $this->context_matches( $value, $actual[ $key ] ) ) {
					return false;
				}
			} elseif ( $actual[ $key ] !== $value ) {
				return false;
			}
		}
		return true;
	}
}
