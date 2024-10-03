<?php
/**
 * Base test case for all WooCommerce tests.
 *
 * @package WooCommerce\Tests
 */

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\CodeHacker;
use Automattic\WooCommerce\Utilities\OrderUtil;
use PHPUnit\Framework\Constraint\IsType;

/**
 * WC Unit Test Case.
 *
 * Provides WooCommerce-specific setup/tear down/assert methods, custom factories,
 * and helper functions.
 *
 * @since 2.2
 */
class WC_Unit_Test_Case extends WP_HTTP_TestCase {

	public const DEFAULT_FLOAT_COMPARISON_DELTA = 1e-10;

	/**
	 * Holds the WC_Unit_Test_Factory instance.
	 *
	 * @var WC_Unit_Test_Factory
	 */
	protected $factory;

	/**
	 * @var int Keeps the count of how many times disable_code_hacker has been invoked.
	 */
	private static $code_hacker_temporary_disables_requested = 0;

	/**
	 * Increase the count of Code Hacker disable requests, and effectively disable it if the count was zero.
	 * Does nothing if the code hacker wasn't enabled when the test suite started running.
	 */
	protected static function disable_code_hacker() {
		if ( CodeHacker::is_enabled() ) {
			CodeHacker::disable();
			self::$code_hacker_temporary_disables_requested = 1;
		} elseif ( self::$code_hacker_temporary_disables_requested > 0 ) {
			++self::$code_hacker_temporary_disables_requested;
		}
	}

	/**
	 * Decrease the count of Code Hacker disable requests, and effectively re-enable it if the count reaches zero.
	 * Does nothing if the count is already zero.
	 */
	protected static function reenable_code_hacker() {
		if ( self::$code_hacker_temporary_disables_requested > 0 ) {
			--self::$code_hacker_temporary_disables_requested;
			if ( 0 === self::$code_hacker_temporary_disables_requested ) {
				CodeHacker::enable();
			}
		}
	}

	/**
	 * Setup test case.
	 *
	 * @since 2.2
	 */
	public function setUp(): void {

		parent::setUp();

		// Add custom factories.
		$this->factory = new WC_Unit_Test_Factory();

		// Setup mock WC session handler.
		add_filter( 'woocommerce_session_handler', array( $this, 'set_mock_session_handler' ) );

		$this->setOutputCallback( array( $this, 'filter_output' ) );

		// Register post types before each test.
		WC_Post_types::register_post_types();
		WC_Post_types::register_taxonomies();

		CodeHacker::reset_hacks();

		// Reset the instance of MockableLegacyProxy that was registered during bootstrap,
		// in order to start the test in a clean state (without anything mocked).
		wc_get_container()->get( LegacyProxy::class )->reset();
	}

	/**
	 * Set up class unit test.
	 *
	 * @since 3.5.0
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Terms are deleted in WP_UnitTestCase::tearDownAfterClass, then e.g. Uncategorized product_cat is missing.
		WC_Install::create_terms();
	}

	/**
	 * Mock the WC session using the abstract class as cookies are not available.
	 * during tests.
	 *
	 * @since  2.2
	 * @return string The $output string, sans newlines and tabs.
	 */
	public function set_mock_session_handler() {
		return 'WC_Mock_Session_Handler';
	}

	/**
	 * Strip newlines and tabs when using expectedOutputString() as otherwise.
	 * the most template-related tests will fail due to indentation/alignment in.
	 * the template not matching the sample strings set in the tests.
	 *
	 * @since 2.2
	 *
	 * @param string $output The captured output.
	 * @return string The $output string, sans newlines and tabs.
	 */
	public function filter_output( $output ) {

		$output = preg_replace( '/[\n]+/S', '', $output );
		$output = preg_replace( '/[\t]+/S', '', $output );

		return $output;
	}

	/**
	 * Throws an exception with an optional message and code.
	 *
	 * Note: can't use `throwException` as that's reserved.
	 *
	 * @since 3.3-dev
	 * @param string $message Optional. The exception message. Default is empty.
	 * @param int    $code    Optional. The exception code. Default is empty.
	 * @throws Exception Containing the given message and code.
	 */
	public function throwAnException( $message = null, $code = null ) {
		$message = $message ? $message : "We're all doomed!";
		throw new Exception( $message, $code );
	}

	/**
	 * Copies a file, temporarily disabling the code hacker.
	 * Use this instead of "copy" in tests for compatibility with the code hacker.
	 *
	 * TODO: Investigate why invoking "copy" within a test with the code hacker active causes the test to fail.
	 *
	 * @param string $source Path to the source file.
	 * @param string $dest The destination path.
	 * @return bool true on success or false on failure.
	 */
	public static function file_copy( $source, $dest ) {
		self::disable_code_hacker();
		$result = copy( $source, $dest );
		self::reenable_code_hacker();

		return $result;
	}

	/**
	 * Create a new user in a given role and set it as the current user.
	 *
	 * @param string $role The role for the user to be created.
	 * @return int The id of the user created.
	 */
	public function login_as_role( $role ) {
		$user_id = $this->factory->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );
		return $user_id;
	}

	/**
	 * Create a new administrator user and set it as the current user.
	 *
	 * @return int The id of the user created.
	 */
	public function login_as_administrator() {
		return $this->login_as_role( 'administrator' );
	}

	/**
	 * Get an instance of a class that has been registered in the dependency injection container.
	 * To get an instance of a legacy class (such as the ones in the 'íncludes' directory) use
	 * 'get_legacy_instance_of' instead.
	 *
	 * @param string $class_name The class name to get an instance of.
	 *
	 * @return mixed The instance.
	 */
	public function get_instance_of( string $class_name ) {
		return wc_get_container()->get( $class_name );
	}

	/**
	 * Get an instance of  legacy class (such as the ones in the 'íncludes' directory).
	 * To get an instance of a class registered in the dependency injection container use 'get_instance_of' instead.
	 *
	 * @param string $class_name The class name to get an instance of.
	 *
	 * @return mixed The instance.
	 */
	public function get_legacy_instance_of( string $class_name ) {
		return wc_get_container()->get( LegacyProxy::class )->get_instance_of( $class_name );
	}

	/**
	 * Reset all the cached resolutions in the dependency injection container, so any further "get"
	 * for shared definitions will generate the instance again.
	 * This may be needed when registering mocks for already resolved shared classes.
	 */
	public function reset_container_resolutions() {
		wc_get_container()->reset_all_resolved();
	}

	/**
	 * Reset all the class registration replacements in the dependency injection container,
	 * so any further "get" will return an instance of the class originally registered.
	 * For this to work with shared definitions 'reset_container_resolutions' is required too.
	 */
	public function reset_container_replacements() {
		wc_get_container()->reset_all_replacements();
	}

	/**
	 * Reset the mock legacy proxy class so that all the registered mocks are unregistered.
	 */
	public function reset_legacy_proxy_mocks() {
		wc_get_container()->get( LegacyProxy::class )->reset();
	}

	/**
	 * Register the function mocks to use in the mockable LegacyProxy.
	 *
	 * @param array $mocks An associative array where keys are function names and values are function replacement callbacks.
	 *
	 * @throws \Exception Invalid parameter.
	 */
	public function register_legacy_proxy_function_mocks( array $mocks ) {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks( $mocks );
	}

	/**
	 * Register the static method mocks to use in the mockable LegacyProxy.
	 *
	 * @param array $mocks An associative array where keys are class names and values are associative arrays, in which keys are method names and values are method replacement callbacks.
	 *
	 * @throws \Exception Invalid parameter.
	 */
	public function register_legacy_proxy_static_mocks( array $mocks ) {
		wc_get_container()->get( LegacyProxy::class )->register_static_mocks( $mocks );
	}

	/**
	 * Register the class mocks to use in the mockable LegacyProxy.
	 *
	 * @param array $mocks An associative array where keys are class names and values are either factory callbacks (optionally with a $class_name argument) or objects.
	 *
	 * @throws \Exception Invalid parameter.
	 */
	public function register_legacy_proxy_class_mocks( array $mocks ) {
		wc_get_container()->get( LegacyProxy::class )->register_class_mocks( $mocks );
	}

	/**
	 * Register the global mocks to use in the mockable LegacyProxy.
	 *
	 * @param array $mocks An associative array where keys are global names and values are the replacements for each global.
	 */
	public function register_legacy_proxy_global_mocks( array $mocks ) {
		wc_get_container()->get( LegacyProxy::class )->register_global_mocks( $mocks );
	}

	/**
	 * Register a callback to be executed when the "exit" method is invoked.
	 *
	 * @param callable|null $mock The callback to be registered, or null to unregister it.
	 */
	public function register_exit_mock( ?callable $mock ) {
		wc_get_container()->get( LegacyProxy::class )->register_exit_mock( $mock );
	}

	/**
	 * Asserts that a certain callable output is equivalent to a given piece of HTML.
	 *
	 * "Equivalent" means that the string representations of the HTML pieces are equal
	 * except for line breaks, tabs and redundant whitespace.
	 *
	 * @param string   $expected The expected HTML.
	 * @param callable $callable The callable that is supposed to output the expected HTML.
	 * @param string   $message Optional error message to display if the assertion fails.
	 */
	protected function assertOutputsHTML( $expected, $callable, $message = '' ) {
		$actual = $this->capture_output_from( $callable );
		$this->assertEqualsHTML( $expected, $actual, $message );
	}

	/**
	 * Asserts that two pieces of HTML are equivalent.
	 *
	 * "Equivalent" means that the string representations of the HTML pieces are equal
	 * except for line breaks, tabs and redundant whitespace.
	 *
	 * @param string $expected The expected HTML.
	 * @param string $actual The HTML that is supposed to be equivalent to the expected one.
	 * @param string $message Optional error message to display if the assertion fails.
	 */
	protected function assertEqualsHTML( $expected, $actual, $message = '' ) {
		$this->assertEquals( $this->normalize_html( $expected ), $this->normalize_html( $actual ), $message );
	}

	/**
	 * Normalizes a block of HTML.
	 * Line breaks, tabs and redundand whitespaces are removed.
	 *
	 * @param string $html The block of HTML to normalize.
	 *
	 * @return string The normalized block.
	 */
	protected function normalize_html( $html ) {
		$html = $this->filter_output( $html );
		$html = str_replace( '&', '&amp;', $html );
		$html = preg_replace( '/> +</', '><', $html );

		$doc                     = new DomDocument();
		$doc->preserveWhiteSpace = false; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$doc->loadHTML( $html );

		return $doc->saveHTML();
	}

	/**
	 * Executes a callable, captures its output and returns it.
	 *
	 * @param callable $callable Callable to execute.
	 * @param mixed    ...$params Parameters to pass to the callable as arguments.
	 *
	 * @return false|string The output generated by the callable, or false if there is an error.
	 */
	protected function capture_output_from( $callable, ...$params ) {
		ob_start();
		call_user_func( $callable, ...$params );
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * Asserts that a variable is of type int.
	 * TODO: After upgrading to PHPUnit 8 or newer, remove this method and replace calls with PHPUnit's built-in 'assertIsInt'.
	 *
	 * @param mixed $actual The value to check.
	 * @param mixed $message Error message to use if the assertion fails.
	 * @return bool mixed True if the value is of integer type, false otherwise.
	 */
	public static function assertIsInteger( $actual, $message = '' ) {
		return self::assertIsInt( $actual, $message );
	}

	/**
	 * Skip the current test on PHP 8.1 and higher.
	 * TODO: Remove this method and its usages once WordPress is compatible with PHP 8.1. Please note that there are multiple copies of this method.
	 */
	protected function skip_on_php_8_1() {
		if ( version_compare( PHP_VERSION, '8.1', '>=' ) ) {
			$this->markTestSkipped( 'Waiting for WordPress compatibility with PHP 8.1' );
		}
	}

	/**
	 * Get recorded tracks event by name.
	 *
	 * @param string $event_name Event name.
	 * @return WC_Tracks_Event|null
	 */
	public function get_tracks_events( $event_name ) {
		$events  = WC_Tracks_Footer_Pixel::get_events();
		$matches = array();

		foreach ( $events as $event ) {
			if ( $event->_en === $event_name ) {
				$matches[] = $event;
			}
		}

		return $matches;
	}

	/**
	 * Clear recorded tracks event.
	 */
	public function clear_tracks_events() {
		$events = WC_Tracks_Footer_Pixel::clear_events();
	}

	/**
	 * Assert that a valid tracks event has been recorded.
	 *
	 * @param string $event_name Event name.
	 */
	public function assertRecordedTracksEvent( $event_name ): void {
		$events = self::get_tracks_events( $event_name );
		$this->assertNotEmpty( $events );
	}

	/**
	 * Assert that a tracks event has not been recorded.
	 *
	 * @param string $event_name Event name.
	 */
	public function assertNotRecordedTracksEvent( $event_name ): void {
		$events = self::get_tracks_events( $event_name );
		$this->assertEmpty( $events );
	}

	/**
	 * Assert that the difference between two floats is smaller than a given delta.
	 *
	 * @param float      $expected The expected value.
	 * @param float      $actual The actual value.
	 * @param float|null $delta The maximum allowed difference, defaults to DEFAULT_FLOAT_COMPARISON_DELTA.
	 * @param string     $message An optional error message to use if the assertion fails.
	 */
	public function assertFloatEquals( $expected, $actual, ?float $delta = null, string $message = '' ) {
		$this->assertEqualsWithDelta( $expected, $actual, $delta ?? self::DEFAULT_FLOAT_COMPARISON_DELTA, $message );
	}

	/**
	 * Mark test skipped when HPOS is enabled.
	 *
	 * @param string $message Message to display when test is skipped.
	 */
	protected function skip_if_hpos_enabled( $message ) {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped( $message );
		}
	}
}
