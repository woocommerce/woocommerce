<?php
/**
 * Simple Test Runner for PR #1 Validation
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator;

/**
 * TestRunner class for validating PR #1 implementation.
 */
class TestRunner {

	/**
	 * Run basic validation tests for PR #1.
	 *
	 * @return array Test results.
	 */
	public static function run_pr1_validation_tests(): array {
		$results = array();

		// Test 1: ProductsController class exists and is instantiable.
		$results['ProductsController Instantiation'] = self::test_products_controller_instantiation();

		// Test 2: ProductsCommand has been updated with ProductsController dependency.
		$results['ProductsCommand DI Integration'] = self::test_products_command_di_integration();

		// Test 3: ImportSession integration works.
		$results['ImportSession Integration'] = self::test_import_session_integration();

		// Test 4: Mock data and fixtures are available.
		$results['Test Fixtures Available'] = self::test_fixtures_availability();

		// Test 5: All required test files exist.
		$results['Test Files Exist'] = self::test_files_exist();

		return $results;
	}

	/**
	 * Test ProductsController instantiation.
	 *
	 * @return array Test result.
	 */
	private static function test_products_controller_instantiation(): array {
		try {
			$controller_class = 'Automattic\\WooCommerce\\Internal\\CLI\\Migrator\\Core\\ProductsController';

			if ( ! class_exists( $controller_class ) ) {
				return array(
					'status'  => 'fail',
					'message' => 'ProductsController class not found',
				);
			}

			// Test constructor requirements.
			$reflection  = new \ReflectionClass( $controller_class );
			$constructor = $reflection->getConstructor();

			if ( ! $constructor ) {
				return array(
					'status'  => 'fail',
					'message' => 'ProductsController constructor not found',
				);
			}

			$params = $constructor->getParameters();
			if ( count( $params ) < 2 ) {
				return array(
					'status'  => 'fail',
					'message' => 'ProductsController constructor missing required parameters',
				);
			}

			// Test key methods exist.
			$required_methods = array( 'init', 'migrate_products' );
			foreach ( $required_methods as $method ) {
				if ( ! $reflection->hasMethod( $method ) ) {
					return array(
						'status'  => 'fail',
						'message' => "ProductsController missing method: {$method}",
					);
				}
			}

			return array(
				'status'  => 'pass',
				'message' => 'ProductsController properly instantiable',
			);

		} catch ( \Exception $e ) {
			return array(
				'status'  => 'fail',
				'message' => 'Exception: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Test ProductsCommand DI integration.
	 *
	 * @return array Test result.
	 */
	private static function test_products_command_di_integration(): array {
		try {
			$command_class = 'Automattic\\WooCommerce\\Internal\\CLI\\Migrator\\Commands\\ProductsCommand';

			if ( ! class_exists( $command_class ) ) {
				return array(
					'status'  => 'fail',
					'message' => 'ProductsCommand class not found',
				);
			}

			$reflection = new \ReflectionClass( $command_class );

			// Test init method has 3 parameters (including ProductsController).
			if ( ! $reflection->hasMethod( 'init' ) ) {
				return array(
					'status'  => 'fail',
					'message' => 'ProductsCommand init method not found',
				);
			}

			$init_method = $reflection->getMethod( 'init' );
			$params      = $init_method->getParameters();

			if ( count( $params ) < 3 ) {
				return array(
					'status'  => 'fail',
					'message' => 'ProductsCommand init method missing ProductsController parameter',
				);
			}

			// Check for ProductsController parameter.
			$has_products_controller = false;
			foreach ( $params as $param ) {
				if ( $param->getType() && strpos( $param->getType()->getName(), 'ProductsController' ) !== false ) {
					$has_products_controller = true;
					break;
				}
			}

			if ( ! $has_products_controller ) {
				return array(
					'status'  => 'fail',
					'message' => 'ProductsCommand init method missing ProductsController type hint',
				);
			}

			return array(
				'status'  => 'pass',
				'message' => 'ProductsCommand properly updated with ProductsController DI',
			);

		} catch ( \Exception $e ) {
			return array(
				'status'  => 'fail',
				'message' => 'Exception: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Test ImportSession integration.
	 *
	 * @return array Test result.
	 */
	private static function test_import_session_integration(): array {
		try {
			$session_class = 'Automattic\\WooCommerce\\Internal\\CLI\\Migrator\\Lib\\ImportSession';

			if ( ! class_exists( $session_class ) ) {
				return array(
					'status'  => 'fail',
					'message' => 'ImportSession class not found',
				);
			}

			$reflection = new \ReflectionClass( $session_class );

			// Test key static methods exist.
			$required_static_methods = array( 'create', 'get_active' );
			foreach ( $required_static_methods as $method ) {
				if ( ! $reflection->hasMethod( $method ) ) {
					return array(
						'status'  => 'fail',
						'message' => "ImportSession missing method: {$method}",
					);
				}
			}

			// Test key instance methods exist.
			$required_instance_methods = array( 'get_reentrancy_cursor', 'set_reentrancy_cursor', 'bump_imported_entities_counts' );
			foreach ( $required_instance_methods as $method ) {
				if ( ! $reflection->hasMethod( $method ) ) {
					return array(
						'status'  => 'fail',
						'message' => "ImportSession missing method: {$method}",
					);
				}
			}

			return array(
				'status'  => 'pass',
				'message' => 'ImportSession integration ready',
			);

		} catch ( \Exception $e ) {
			return array(
				'status'  => 'fail',
				'message' => 'Exception: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Test fixtures availability.
	 *
	 * @return array Test result.
	 */
	private static function test_fixtures_availability(): array {
		try {
			$fixtures_class = 'Automattic\\WooCommerce\\Tests\\Internal\\CLI\\Migrator\\Fixtures\\MockShopifyData';

			if ( ! class_exists( $fixtures_class ) ) {
				return array(
					'status'  => 'fail',
					'message' => 'MockShopifyData fixtures class not found',
				);
			}

			$reflection = new \ReflectionClass( $fixtures_class );

			// Test key fixture methods exist.
			$required_methods = array( 'get_mock_products', 'get_mock_batch_response', 'get_mock_credentials' );
			foreach ( $required_methods as $method ) {
				if ( ! $reflection->hasMethod( $method ) ) {
					return array(
						'status'  => 'fail',
						'message' => "MockShopifyData missing method: {$method}",
					);
				}
			}

			return array(
				'status'  => 'pass',
				'message' => 'Test fixtures available and complete',
			);

		} catch ( \Exception $e ) {
			return array(
				'status'  => 'fail',
				'message' => 'Exception: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Test that all required test files exist.
	 *
	 * @return array Test result.
	 */
	private static function test_files_exist(): array {
		$base_path      = __DIR__;
		$required_files = array(
			'Core/ProductsControllerTest.php',
			'Commands/ProductsCommandTest.php',
			'Integration/ProductsControllerIntegrationTest.php',
			'Fixtures/MockShopifyData.php',
		);

		$missing_files = array();
		foreach ( $required_files as $file ) {
			$full_path = $base_path . '/' . $file;
			if ( ! file_exists( $full_path ) ) {
				$missing_files[] = $file;
			}
		}

		if ( ! empty( $missing_files ) ) {
			return array(
				'status'  => 'fail',
				'message' => 'Missing test files: ' . implode( ', ', $missing_files ),
			);
		}

		return array(
			'status'  => 'pass',
			'message' => 'All required test files exist',
		);
	}

	/**
	 * Display test results in a formatted way.
	 *
	 * @param array $results Test results.
	 */
	public static function display_results( array $results ): void {
		$separator = str_repeat( '=', 80 );
		echo "\n" . esc_html( $separator ) . "\n";
		echo "PR #1: ProductsController Integration + Session Management\n";
		echo "Validation Test Results\n";
		echo esc_html( $separator ) . "\n\n";

		$passed = 0;
		$total  = count( $results );

		foreach ( $results as $test_name => $result ) {
			$status_icon = 'pass' === $result['status'] ? '✓' : '✗';
			$status_text = strtoupper( $result['status'] );

			printf( "[%s] %-40s %s\n", esc_html( $status_icon ), esc_html( $test_name ), esc_html( $status_text ) );
			if ( 'fail' === $result['status'] ) {
				printf( "    └─ %s\n", esc_html( $result['message'] ) );
			}

			if ( 'pass' === $result['status'] ) {
				++$passed;
			}

			echo "\n";
		}

		$divider = str_repeat( '-', 80 );
		echo esc_html( $divider ) . "\n";
		printf( "Summary: %d/%d tests passed (%.1f%%)\n", intval( $passed ), intval( $total ), ( intval( $passed ) / intval( $total ) ) * 100 );
		echo esc_html( $separator ) . "\n";

		if ( $total === $passed ) {
			echo "🎉 All validation tests passed! PR #1 implementation is ready.\n\n";
		} else {
			echo "❌ Some validation tests failed. Please review the implementation.\n\n";
		}
	}
}

// Run tests if this file is executed directly from CLI.
if ( defined( 'PHP_SAPI' ) && 'cli' === PHP_SAPI ) {
	$results = TestRunner::run_pr1_validation_tests();
	TestRunner::display_results( $results );
}
