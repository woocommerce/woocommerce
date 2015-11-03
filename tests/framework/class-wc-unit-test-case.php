<?php
/**
 * WC Unit Test Case
 *
 * Provides WooCommerce-specific setup/tear down/assert methods, custom factories,
 * and helper functions.
 *
 * @since 2.2
 */
class WC_Unit_Test_Case extends WP_UnitTestCase {

	/** @var \WC_Unit_Test_Factory instance */
	protected $factory;

	/**
	 * Setup test case.
	 *
	 * @since 2.2
	 */
	public function setUp() {

		parent::setUp();

		// Add custom factories
		$this->factory = new WC_Unit_Test_Factory();

		// Setup mock WC session handler
		add_filter( 'woocommerce_session_handler', array( $this, 'set_mock_session_handler' ) );

		$this->setOutputCallback( array( $this, 'filter_output' ) );

		// Register post types before each test
		WC_Post_types::register_post_types();
		WC_Post_types::register_taxonomies();
	}

	/**
	 * Mock the WC session using the abstract class as cookies are not available.
	 * during tests.
	 *
	 * @since  2.2
	 * @return string
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
	 */
	public function filter_output( $output ) {

		$output = preg_replace( '/[\n]+/S', '', $output );
		$output = preg_replace( '/[\t]+/S', '', $output );

		return $output;
	}

	/**
	 * Asserts thing is not WP_Error.
	 *
	 * @since 2.2
	 * @param mixed  $actual
	 * @param string $message
	 */
	public function assertNotWPError( $actual, $message = '' ) {
		$this->assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts thing is WP_Error.
	 *
	 * @param mixed  $actual
	 * @param string $message
	 */
	public function assertIsWPError( $actual, $message = '' ) {
		$this->assertInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Backport assertNotFalse to PHPUnit 3.6.12 which only runs in PHP 5.2.
	 *
	 * @since  2.2
	 * @param  $condition
	 * @param  string $message
	 * @return mixed
	 */
	public static function assertNotFalse( $condition, $message = '' ) {

		if ( version_compare( phpversion(), '5.3', '<' ) ) {

			self::assertThat( $condition, self::logicalNot( self::isFalse() ), $message );

		} else {

			parent::assertNotFalse( $condition, $message );
		}
	}

}
