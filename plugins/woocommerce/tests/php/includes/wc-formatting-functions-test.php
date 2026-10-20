<?php
declare( strict_types = 1 );

/**
 * Formatting functions tests
 *
 * @package WooCommerce\Tests\Formatting.
 */

/**
 * Class WC_Formatting_Functions_Test
 */
class WC_Formatting_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * User IDs to sanitize coupon codes as, keyed by role, with 0 for a guest.
	 *
	 * @var int[]
	 */
	private static $user_ids_by_role = array();

	/**
	 * Create the users shared by the tests in this class.
	 *
	 * @param WP_UnitTest_Factory $factory Test data factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$user_ids_by_role = array(
			'guest'         => 0,
			'shop_manager'  => $factory->user->create( array( 'role' => 'shop_manager' ) ),
			'administrator' => $factory->user->create( array( 'role' => 'administrator' ) ),
		);
	}

	/**
	 * Starts each test with an empty WC_Admin_Settings errors array.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->wc_admin_settings_errors_property()->setValue( null, array() );
	}

	/**
	 * Resets the static WC_Admin_Settings errors so they do not leak into other tests.
	 */
	public function tearDown(): void {
		try {
			$this->wc_admin_settings_errors_property()->setValue( null, array() );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Data provider for test_wc_sanitize_coupon_code.
	 *
	 * @return array[]
	 */
	public function data_provider_test_wc_sanitize_coupon_code(): array {
		return array(
			'null'                                  => array( null, '' ),
			'plain code'                            => array( 'DUMMYCOUPON', 'DUMMYCOUPON' ),
			'ampersand is stored encoded'           => array( 'a&a', 'a&amp;a' ),
			'encoded ampersand'                     => array( 'a&amp;a', 'a&amp;a' ),
			'double-encoded ampersand'              => array( 'a&amp;amp;a', 'a&amp;a' ),
			'ampersand before a legacy entity name' => array( '50&cent', '50&amp;cent' ),
			'ampersand before a semicolon'          => array( 'R&D;x', 'R&amp;D;x' ),
			'non-ASCII with ampersand'              => array( 'ÜBER&ÄRGER', 'ÜBER&amp;ÄRGER' ),
			'apostrophe is not slashed'             => array( "test's", "test's" ),
			'encoded apostrophe is kept as stored'  => array( 'a&#039;b', 'a&#039;b' ),
			'named apostrophe is kept as stored'    => array( 'a&apos;b', 'a&apos;b' ),
			'double quotes'                         => array( 'TEST"2024"', 'TEST"2024"' ),
			'encoded double quotes'                 => array( 'TEST&quot;2024&quot;', 'TEST"2024"' ),
			'named entity is decoded'               => array( 'a&copy;b', 'a©b' ),
			'non-breaking space entity is decoded'  => array( 'a&nbsp;b', "a\u{00A0}b" ),
			'percent followed by digits is kept'    => array( '%20test', '%20test' ),
			'percent sign'                          => array( '50%off', '50%off' ),
			'greater-than sign'                     => array( 'a>b', 'a&gt;b' ),
			'less-than sign before a digit'         => array( '10<20', '10&lt;20' ),
			'less-than sign before a letter'        => array( 'a<b', 'a&lt;b' ),
			'backslash is kept'                     => array( 'a\\b', 'a\\b' ),
			'surrounding whitespace is trimmed'     => array( '  padded  ', 'padded' ),
			'control characters are removed'        => array( "a\0b\x07c", 'abc' ),
			'tag-like text with a numeric name'     => array( 'x<5>y', 'x&lt;5&gt;y' ),
			'HTML tags are escaped'                 => array( '<b>bold</b>', '&lt;b&gt;bold&lt;/b&gt;' ),
		);
	}

	/**
	 * @testdox Should sanitize coupon codes the same way for guests, shop managers and administrators.
	 *
	 * @dataProvider data_provider_test_wc_sanitize_coupon_code
	 *
	 * @param string|null $input    Input for wc_sanitize_coupon_code().
	 * @param string      $expected Expected result.
	 */
	public function test_wc_sanitize_coupon_code( ?string $input, string $expected ): void {
		foreach ( self::$user_ids_by_role as $role => $user_id ) {
			wp_set_current_user( $user_id );

			$sanitized = wc_sanitize_coupon_code( $input );

			$this->assertSame( $expected, $sanitized, "Unexpected result for {$role}" );
			$this->assertSame( $sanitized, wc_sanitize_coupon_code( $sanitized ), "Sanitizing again should not change the code for {$role}" );
		}
	}

	/**
	 * Data provider for test_wc_format_postcode.
	 *
	 * @return array[]
	 * @see WC_Tests_Formatting_Functions::test_wc_format_postcode for US, GB, BR, JP, NL, LV
	 */
	public function data_provider_test_wc_format_postcode(): array {
		$ie = array(
			array( 'D02 AF30', 'D02AF30', 'IE' ),
		);

		$pt = array(
			array( '1000-205', '1000205', 'PT' ),
		);

		$dk = array(
			array( '1234', '1234', 'DK' ),
			array( 'DK-1234', 'DK-1234', 'DK' ),
			array( 'DK-1234', 'dk-1234', 'DK' ),
		);

		$se = array(
			array( '113 52', '11352', 'SE' ),
		);

		$sk = array(
			array( '811 02', '81102', 'SK' ),
			array( 'SK-811 02', 'SK-81102', 'SK' ),
			array( 'SK-811 02', 'sk-81102', 'SK' ),
		);

		$cz = array(
			array( '115 03', '11503', 'CZ' ),
			array( 'CZ-115 03', 'CZ-11503', 'CZ' ),
			array( 'CZ-115 03', 'cz-11503', 'CZ' ),
		);

		return array_merge( $ie, $pt, $dk, $se, $sk, $cz );
	}

	/**
	 * Test wc_format_postcode() function.
	 *
	 * @dataProvider data_provider_test_wc_format_postcode
	 *
	 * @param string $assert Expected result.
	 * @param string $postcode Postcode input for wc_format_postcode().
	 * @param string $country Country input for wc_format_postcode().
	 */
	public function test_wc_format_postcode( string $assert, string $postcode, string $country ) {
		$this->assertSame( $assert, wc_format_postcode( $postcode, $country ), "Test formatting of $postcode postcodes." );
	}

	/**
	 * Data provider for test_wc_format_option_price_separators.
	 *
	 * @return array[]
	 */
	public function data_provider_wc_format_option_price_separators(): array {
		return array(
			'thousand sep: comma'           => array( 'woocommerce_price_thousand_sep', ',', ',', false ),
			'thousand sep: period'          => array( 'woocommerce_price_thousand_sep', '.', '.', false ),
			'thousand sep: space'           => array( 'woocommerce_price_thousand_sep', ' ', ' ', false ),
			'thousand sep: two spaces'      => array( 'woocommerce_price_thousand_sep', '  ', '  ', false ),
			'thousand sep: empty'           => array( 'woocommerce_price_thousand_sep', '', '', false ),
			'thousand sep: nbsp entity'     => array( 'woocommerce_price_thousand_sep', '&nbsp;', "\u{00A0}", false ),
			'thousand sep: comma entity'    => array( 'woocommerce_price_thousand_sep', '&#44;', ',', false ),
			'thousand sep: not submitted'   => array( 'woocommerce_price_thousand_sep', null, '|', false ),
			'thousand sep: single digit'    => array( 'woocommerce_price_thousand_sep', '1', '|', true ),
			'thousand sep: digit+symbol'    => array( 'woocommerce_price_thousand_sep', '1,', '|', true ),
			'thousand sep: digit entity'    => array( 'woocommerce_price_thousand_sep', '&#49;', '|', true ),
			'thousand sep: fullwidth digit' => array( 'woocommerce_price_thousand_sep', '１', '|', true ),
			'thousand sep: array'           => array( 'woocommerce_price_thousand_sep', array( '1' ), '|', true ),
			'decimal sep: period'           => array( 'woocommerce_price_decimal_sep', '.', '.', false ),
			'decimal sep: single digit'     => array( 'woocommerce_price_decimal_sep', '2', '|', true ),
			'decimal sep: arabic digit'     => array( 'woocommerce_price_decimal_sep', '٢', '|', true ),
		);
	}

	/**
	 * @testdox wc_format_option_price_separators should keep valid separators and fall back to the stored one when the input is rejected.
	 *
	 * @dataProvider data_provider_wc_format_option_price_separators
	 *
	 * @param string $option_id     The option being saved.
	 * @param mixed  $raw_value     The raw input being saved, null when the field was not submitted.
	 * @param mixed  $expected      The value the filter should return.
	 * @param bool   $expects_error Whether the input should be rejected with a settings error.
	 */
	public function test_wc_format_option_price_separators( string $option_id, $raw_value, $expected, bool $expects_error ): void {
		update_option( $option_id, '|' );

		$result = wc_format_option_price_separators( $raw_value, array( 'id' => $option_id ), $raw_value );

		// Compare the separator as displayed, since WordPress 7.2's wp_kses() stores character references as the characters themselves.
		$this->assertSame( $expected, html_entity_decode( $result, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

		$errors = $this->get_wc_admin_settings_errors();

		$this->assertCount( $expects_error ? 1 : 0, $errors, 'An error should be added only when the separator is rejected.' );

		if ( $expects_error ) {
			$this->assertStringContainsString( 'cannot contain numbers', end( $errors ), 'Error message should mention numbers.' );
		}
	}

	/**
	 * @testdox wc_format_option_price_separators should return an empty string when nothing is stored.
	 */
	public function test_wc_format_option_price_separators_returns_empty_string_when_nothing_stored(): void {
		delete_option( 'woocommerce_price_thousand_sep' );

		$result = wc_format_option_price_separators( '1', array( 'id' => 'woocommerce_price_thousand_sep' ), '1' );

		$this->assertSame( '', $result, 'A rejected separator should return an empty string when the option is not stored.' );
	}

	/**
	 * @testdox wc_format_option_price_separators should keep a stored empty separator on rejection.
	 */
	public function test_wc_format_option_price_separators_keeps_stored_empty_separator(): void {
		update_option( 'woocommerce_price_thousand_sep', '' );

		$result = wc_format_option_price_separators( '1', array( 'id' => 'woocommerce_price_thousand_sep' ), '1' );

		$this->assertSame( '', $result, 'A deliberately empty separator should survive a rejected save.' );
	}

	/**
	 * @testdox wc_format_option_price_separators should discard a stored value that is not a string.
	 */
	public function test_wc_format_option_price_separators_discards_non_string_stored_value(): void {
		update_option( 'woocommerce_price_thousand_sep', array( '1' ) );

		$result = wc_format_option_price_separators( '1', array( 'id' => 'woocommerce_price_thousand_sep' ), '1' );

		$this->assertSame( '', $result, 'A stored value that is not a string should not be handed back.' );
	}

	/**
	 * Reads the private static $errors array from WC_Admin_Settings via reflection.
	 *
	 * @return array
	 */
	private function get_wc_admin_settings_errors(): array {
		return $this->wc_admin_settings_errors_property()->getValue();
	}

	/**
	 * Returns the private static $errors property of WC_Admin_Settings.
	 *
	 * @return ReflectionProperty
	 */
	private function wc_admin_settings_errors_property(): ReflectionProperty {
		$reflection = new \ReflectionClass( WC_Admin_Settings::class );
		$property   = $reflection->getProperty( 'errors' );
		$property->setAccessible( true );
		return $property;
	}

	/**
	 * Test wc_is_stock_amount_integer function.
	 *
	 * @testdox wc_is_stock_amount_integer should return true when stock amounts are integers and false when they are floats.
	 */
	public function test_wc_is_stock_amount_integer() {
		// Remove all filters from woocommerce_stock_amount.
		remove_all_filters( 'woocommerce_stock_amount' );

		// Test with floatval applied to the filter.
		add_filter( 'woocommerce_stock_amount', 'floatval' );
		$this->assertFalse( wc_is_stock_amount_integer(), 'Should return false when floatval is applied to stock amount filter.' );

		// Remove floatval filter and add intval filter.
		remove_all_filters( 'woocommerce_stock_amount' );
		add_filter( 'woocommerce_stock_amount', 'intval' );
		$this->assertTrue( wc_is_stock_amount_integer(), 'Should return true when intval is applied to stock amount filter.' );
	}

	/**
	 * @testdox Saving an unchanged hold stock value should preserve the pending cancellation action.
	 */
	public function test_unchanged_hold_stock_value_preserves_pending_cancellation_action(): void {
		$scheduled_time = time() + ( 2 * HOUR_IN_SECONDS );

		update_option( 'woocommerce_hold_stock_minutes', '60' );
		as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
		as_schedule_single_action( $scheduled_time, 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' );

		wc_format_option_hold_stock_minutes( '60', array(), '60' );

		$this->assertSame(
			$scheduled_time,
			as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders' ),
			'An unchanged hold stock value should not delay the existing cancellation action.'
		);
	}

	/**
	 * @testdox Saving an unchanged hold stock value should recreate a missing cancellation action.
	 */
	public function test_unchanged_hold_stock_value_recreates_missing_cancellation_action(): void {
		update_option( 'woocommerce_hold_stock_minutes', '60' );
		as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );

		$expected_time = time() + HOUR_IN_SECONDS;
		wc_format_option_hold_stock_minutes( '60', array(), '60' );

		$this->assertEqualsWithDelta(
			$expected_time,
			as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders' ),
			1,
			'An unchanged hold stock value should recreate a missing cancellation action.'
		);
	}

	/**
	 * @testdox Saving a changed hold stock value should replace the cancellation action at the new interval.
	 */
	public function test_changed_hold_stock_value_replaces_cancellation_action(): void {
		$scheduled_time = time() + ( 2 * HOUR_IN_SECONDS );

		update_option( 'woocommerce_hold_stock_minutes', '60' );
		as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
		as_schedule_single_action( $scheduled_time, 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' );

		$expected_time = time() + ( 30 * MINUTE_IN_SECONDS );
		wc_format_option_hold_stock_minutes( '30', array(), '30' );
		$next_scheduled_time = as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders' );

		$this->assertNotSame( $scheduled_time, $next_scheduled_time, 'A changed hold stock value should replace the existing cancellation action.' );
		$this->assertEqualsWithDelta( $expected_time, $next_scheduled_time, 1, 'The replacement action should use the new hold stock interval.' );
	}

	/**
	 * @testdox Saving a blank hold stock value should remove the cancellation action when the option is missing.
	 */
	public function test_blank_hold_stock_value_removes_cancellation_action_when_option_is_missing(): void {
		delete_option( 'woocommerce_hold_stock_minutes' );
		as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
		as_schedule_single_action( time() + HOUR_IN_SECONDS, 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' );

		wc_format_option_hold_stock_minutes( '', array(), '' );

		$this->assertFalse(
			as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders' ),
			'A blank hold stock value should remove the cancellation action.'
		);
	}
}
