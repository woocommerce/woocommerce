<?php
/**
 * Base test case for all WooCommerce tests.
 *
 * @package WooCommerce\Tests
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\CodeHacker;

/**
 * WC Unit Test Case.
 *
 * Provides WooCommerce-specific setup/tear down/assert methods, custom factories,
 * and helper functions.
 *
 * @since 2.2
 */
class WC_Unit_Test_Case extends WP_HTTP_TestCase {

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
			self::$code_hacker_temporary_disables_requested++;
		}
	}

	/**
	 * Decrease the count of Code Hacker disable requests, and effectively re-enable it if the count reaches zero.
	 * Does nothing if the count is already zero.
	 */
	protected static function reenable_code_hacker() {
		if ( self::$code_hacker_temporary_disables_requested > 0 ) {
			self::$code_hacker_temporary_disables_requested--;
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
	public function setUp() {

		parent::setUp();

		// Add custom factories.
		$this->factory = new WC_Unit_Test_Factory();

		// Setup mock WC session handler.
		add_filter( 'woocommerce_session_handler', array( $this, 'set_mock_session_handler' ) );

		$this->setOutputCallback( array( $this, 'filter_output' ) );

		// Register post types before each test.
		WC_Post_types::register_post_types();
		WC_Post_types::register_taxonomies();
	}

	/**
	 * Set up class unit test.
	 *
	 * @since 3.5.0
	 */
	public static function setUpBeforeClass() {
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
}
