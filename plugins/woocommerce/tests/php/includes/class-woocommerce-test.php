<?php

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Utilities\LegacyRestApiStub;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;

/**
 * Unit tests for the WooCommerce class.
 */
class WooCommerce_Test extends \WC_Unit_Test_Case {
	use LoggerSpyTrait;

	/**
	 * @testdox Should omit arguments from fatal error backtraces while preserving call sites.
	 * @testWith ["#0 /tmp/gateway.php(12): Processor->charge('378282246310005', '2440')", "#0 /tmp/gateway.php(12): Processor->charge()"]
	 *           ["#0 /tmp/商店/café.php(12): Paiement\\支付->دفع('378282246310005', '2440')", "#0 /tmp/商店/café.php(12): Paiement\\支付->دفع()"]
	 *           ["#0 /tmp/gateway.php(12): {closure:/tmp/gateway.php:8}('378282246310005', '2440')", "#0 /tmp/gateway.php(12): {closure:/tmp/gateway.php:8}()"]
	 *           ["#0 [internal function]: call_user_func('378282246310005', '2440')", "#0 [internal function]: call_user_func()"]
	 *           ["#0 [internal function]: pay('378282246310005', 'x(1): y(', '2440')", "#0 [internal function]: pay()"]
	 *           ["#0 /tmp/gateway.php(12): Processor->charge('378282246310005', '2440'", "#0 /tmp/gateway.php(12): Processor->charge()"]
	 *           ["#0 /tmp/gateway.php(12): Processor->{closure:Processor::charge():8}('378282246310005', '2440')", "#0 /tmp/gateway.php(12): Processor->{closure:Processor::charge():8}()"]
	 *           ["#0 /tmp/gateway.php(12): Processor->charge('a(1): b', '378282246310005', '2440(')", "#0 /tmp/gateway.php(12): Processor->charge()"]
	 *           ["#0 C:\\gateway.php(12): Processor->charge('378282246310005', '2440')\r\n#1 {main}", "#0 C:\\gateway.php(12): Processor->charge()"]
	 *           ["#0 /tmp/gateway.php(12): Processor->charge('378282246310005', '\\n2440')", "#0 /tmp/gateway.php(12): Processor->charge()"]
	 *           ["#0 /tmp/gateway.php(12): Processor->charge('x\\n#1 a(1): b', '378282246310005', '2440', Object(Foo))", "#0 /tmp/gateway.php(12): Processor->charge()"]
	 *           ["#0 /tmp/gateway.php(12): Processor->charge('x\\n#1 a', '378282246310005', '(1): foo(', '2440')", "#0 /tmp/gateway.php(12): Processor->charge()"]
	 *           ["#0 /tmp/gateway.php(12): class@anonymous->charge('378282246310005', '2440')", "#0 /tmp/gateway.php(12): class@anonymous->charge()"]
	 *           ["#0 /tmp/gateway.php(12) : eval()'d code(1): Processor->charge('378282246310005', '2440')", "#0 /tmp/gateway.php(12) : eval()'d code(1): Processor->charge()"]
	 *           ["#0 /tmp/gateway.php(12): Processor->{closure:{closure:Processor::charge():8}:9}('378282246310005', '2440')", "#0 /tmp/gateway.php(12): Processor->{closure:{closure:Processor::charge():8}:9}()"]
	 *           ["#0 /tmp/gateway.php(12): class@anonymous->{closure:class@anonymous('378282246310005', '2440')", "#0 /tmp/gateway.php(12): class@anonymous->{closure:class@anonymous()"]
	 *           ["#0 /tmp/gateway.php(12): Processor->charge('x\n#1 a(1): {closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}{closure:a}', '378282246310005', '2440')", "#0 /tmp/gateway.php(12): Processor->charge()"]
	 *           ["#0 /srv/John's \"Store\", Inc/gateway.php(12): {closure:/srv/John's \"Store\", Inc/gateway.php:8}('378282246310005', '2440')", "#0 /srv/John's \"Store\", Inc/gateway.php(12): {closure:/srv/John's \"Store\", Inc/gateway.php:8}()"]
	 *           ["#0 /tmp/gateway.php(12): Processor->charge('378282246310005', '2440')\nNext Exception: 378282246310005 in /tmp/gateway.php:12\n  thrown in /tmp/gateway.php on line 12", "#0 /tmp/gateway.php(12): Processor->charge()"]
	 *
	 * @param string $frame          PHP fatal error trace frame.
	 * @param string $expected_frame Trace frame without arguments.
	 */
	public function test_omits_arguments_from_fatal_error_backtraces( string $frame, string $expected_frame ): void {
		FunctionsMockerHack::add_function_mocks(
			array(
				'error_get_last' => static function () use ( $frame ) {
					return array(
						'type'    => E_ERROR,
						'message' => 'Uncaught TypeError: Fixture payment failure.' . PHP_EOL . 'Stack trace:' . PHP_EOL . $frame . PHP_EOL . '#1 {main}',
						'file'    => '/tmp/gateway.php',
						'line'    => 12,
					);
				},
			)
		);

		WC()->log_errors();

		$this->assertLogged( 'critical', 'Fixture payment failure.', array( 'source' => 'fatal-errors' ) );
		$context = $this->captured_logs[0]['context'];
		$this->assertContains( $expected_frame, $context['backtrace'], 'Fatal logs must preserve call sites without arguments.' );
		$this->assertContains( '#1 {main}', $context['backtrace'], 'The final frame must remain available.' );
		$this->assertStringNotContainsString( '378282246310005', wp_json_encode( $this->captured_logs ), 'Fatal logs must omit card numbers.' );
		$this->assertStringNotContainsString( '2440', wp_json_encode( $this->captured_logs ), 'Fatal logs must omit CVCs.' );
	}

	/**
	 * @testdox Should keep frames from every chained exception when a message contains "Stack trace:".
	 */
	public function test_keeps_chained_fatal_error_frames(): void {
		FunctionsMockerHack::add_function_mocks(
			array(
				'error_get_last' => static function () {
					return array(
						'type'    => E_ERROR,
						'message' => implode(
							PHP_EOL,
							array(
								'Uncaught RuntimeException: See Stack trace: below.',
								'Stack trace:',
								"#0 /tmp/gateway.php(12): Processor->charge('378282246310005')",
								'#1 {main}',
								'',
								'Next Exception: Payment failed.',
								'Stack trace:',
								"#0 /tmp/checkout.php(30): Checkout->pay('2440')",
								'#1 {main}',
							)
						),
						'file'    => '/tmp/checkout.php',
						'line'    => 30,
					);
				},
			)
		);

		WC()->log_errors();

		$backtrace = $this->captured_logs[0]['context']['backtrace'];
		$this->assertContains( '#0 /tmp/gateway.php(12): Processor->charge()', $backtrace, 'Frames after a message that contains "Stack trace:" must be kept.' );
		$this->assertContains( '#0 /tmp/checkout.php(30): Checkout->pay()', $backtrace, 'Frames of chained exceptions must be kept.' );
		$this->assertContains( 'Next Exception', $backtrace, 'Chained exception classes must be kept without their messages.' );
		$this->assertStringNotContainsString( 'Payment failed.', wp_json_encode( $this->captured_logs ), 'Chained exception messages must be omitted.' );
		$this->assertStringNotContainsString( '378282246310005', wp_json_encode( $this->captured_logs ), 'Fatal logs must omit card numbers.' );
		$this->assertStringNotContainsString( '2440', wp_json_encode( $this->captured_logs ), 'Fatal logs must omit CVCs.' );
	}

	/**
	 * @testdox Should keep the frames after a very long trace line.
	 */
	public function test_keeps_frames_after_very_long_trace_line(): void {
		FunctionsMockerHack::add_function_mocks(
			array(
				'error_get_last' => static function () {
					return array(
						'type'    => E_ERROR,
						'message' => implode(
							PHP_EOL,
							array(
								'Uncaught Exception: Fixture payment failure.',
								'Stack trace:',
								'#0 ' . str_repeat( 'a', 1000000 ),
								"#1 /tmp/gateway.php(12): Processor->charge('378282246310005')",
								'#2 {main}',
							)
						),
						'file'    => '/tmp/gateway.php',
						'line'    => 12,
					);
				},
			)
		);

		WC()->log_errors();

		$backtrace = $this->captured_logs[0]['context']['backtrace'];
		$this->assertContains( '#1 /tmp/gateway.php(12): Processor->charge()', $backtrace, 'A very long line must not drop the frames after it.' );
		$this->assertContains( '#2 {main}', $backtrace, 'The final frame must remain available.' );
	}

	/**
	 * The default URI.
	 *
	 * @var string
	 */
	private static $default_uri;

	/**
	 * Store the default URI.
	 *
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$default_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}


	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}


	/**
	 * Restore the default URI.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$_SERVER['REQUEST_URI'] = self::$default_uri;
	}

	/**
	 * Test that the $api property is defined and holds an instance of LegacyRestApiStub
	 * (the Legacy REST API was removed in WooCommerce 9.0).
	 */
	public function test_api_property(): void {
		$this->assertInstanceOf( LegacyRestApiStub::class, WC()->api );
	}

	/**
	 * Test that the rest api returns false when it is not an rest api request.
	 */
	public function test_rest_api_returns_false() {
		$this->assertEquals( WC()->is_rest_api_request(), false );
	}

	/**
	 * Test that the rest api returns true when it is an rest api request.
	 */
	public function test_rest_api_returns_true() {
		// Set the request uri to a rest api request.
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/v3/products';
		$this->assertEquals( WC()->is_rest_api_request(), true );
	}

	/**
	 * @testdox Should detect a REST API request that uses plain permalinks (?rest_route=).
	 */
	public function test_is_rest_api_request_returns_true_for_plain_permalinks(): void {
		$_SERVER['REQUEST_URI'] = '/index.php?rest_route=/wc/v3/products';
		$_GET['rest_route']     = '/wc/v3/products';

		$this->assertTrue( WC()->is_rest_api_request(), 'A ?rest_route= request should be detected as a REST API request.' );
	}

	/**
	 * @testdox Should not detect a request with an empty rest_route parameter as a REST API request.
	 */
	public function test_is_rest_api_request_returns_false_for_empty_rest_route(): void {
		$_SERVER['REQUEST_URI'] = '/index.php?rest_route=';
		$_GET['rest_route']     = '';

		$this->assertFalse( WC()->is_rest_api_request(), 'An empty rest_route parameter should not be detected as a REST API request.' );
	}

	/**
	 * Restore the request globals after each test.
	 */
	public function tearDown(): void {
		unset( $_GET['rest_route'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		parent::tearDown();
	}

	/**
	 * @testdox Should return false when the request is not a Store API request.
	 */
	public function test_is_store_api_request_returns_false_for_non_store_request(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc-admin/options';

		$this->assertFalse( WC()->is_store_api_request(), 'A non-Store API WooCommerce REST request should not be detected as Store API.' );
	}

	/**
	 * @testdox Should detect a Store API request that uses pretty permalinks.
	 */
	public function test_is_store_api_request_returns_true_for_pretty_permalinks(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart';

		$this->assertTrue( WC()->is_store_api_request(), 'A /wp-json/wc/store/ path should be detected as Store API.' );
	}

	/**
	 * @testdox Should detect a Store API request that uses plain permalinks (?rest_route=).
	 */
	public function test_is_store_api_request_returns_true_for_plain_permalinks(): void {
		$_SERVER['REQUEST_URI'] = '/index.php?rest_route=/wc/store/v1/cart';
		$_GET['rest_route']     = '/wc/store/v1/cart';

		$this->assertTrue( WC()->is_store_api_request(), 'A ?rest_route=/wc/store/ request should be detected as Store API.' );
	}

	/**
	 * @testdox Should detect a Store API request whose route uses different letter case, as the REST server matches routes case-insensitively.
	 * @testWith ["/wp-json/wc/Store/v1/cart", ""]
	 *           ["/WP-JSON/WC/STORE/v1/cart", ""]
	 *           ["/index.php?rest_route=/wc/Store/v1/cart", "/wc/Store/v1/cart"]
	 *
	 * @param string $request_uri Request URI.
	 * @param string $rest_route  The rest_route query argument, or empty for none.
	 */
	public function test_is_store_api_request_ignores_letter_case( string $request_uri, string $rest_route ): void {
		$_SERVER['REQUEST_URI'] = $request_uri;
		if ( '' !== $rest_route ) {
			$_GET['rest_route'] = $rest_route;
		}

		$this->assertTrue( WC()->is_store_api_request() );
	}

	/**
	 * @testdox Should detect a Store API request with a percent-encoded path, as WordPress also matches rewrite rules against the decoded path.
	 * @testWith ["/wp%2Djson/wc/store/v1/cart"]
	 *           ["/%77p-json/wc/store/v1/cart"]
	 *           ["/wp-json%2Fwc%2Fstore%2Fv1%2Fcart"]
	 *           ["/wp%2Djson/wc/st%6Fre/v1/cart"]
	 *
	 * @param string $request_uri Request URI.
	 */
	public function test_is_store_api_request_decodes_the_path( string $request_uri ): void {
		$_SERVER['REQUEST_URI'] = $request_uri;

		$this->assertTrue( WC()->is_store_api_request() );
	}

	/**
	 * @testdox Should not detect a Store API request from a double-encoded path or an encoded query argument, as WordPress doesn't route these to the Store API.
	 * @testWith ["/wp%252Djson/wc/store/v1/cart"]
	 *           ["/some-page/?arg=/wp%2Djson/wc/store/"]
	 *
	 * @param string $request_uri Request URI.
	 */
	public function test_is_store_api_request_decodes_the_path_only_once( string $request_uri ): void {
		$_SERVER['REQUEST_URI'] = $request_uri;

		$this->assertFalse( WC()->is_store_api_request() );
	}

	/**
	 * @testdox Should not detect a non-Store API plain-permalink request as Store API.
	 */
	public function test_is_store_api_request_returns_false_for_plain_permalinks_non_store(): void {
		$_SERVER['REQUEST_URI'] = '/index.php?rest_route=/wc-admin/options';
		$_GET['rest_route']     = '/wc-admin/options';

		$this->assertFalse( WC()->is_store_api_request(), 'A non-Store API ?rest_route= request should not be detected as Store API.' );
	}

	/**
	 * @testdox Should not detect a Store-API-like value in a query argument as a Store API request.
	 */
	public function test_is_store_api_request_returns_false_for_rest_like_query_arg(): void {
		$_SERVER['REQUEST_URI'] = '/some-page/?arg=/wp-json/wc/store/v1/cart';

		$this->assertFalse( WC()->is_store_api_request(), 'A REST-like value in a query argument should not be detected as Store API.' );
	}

	/**
	 * @testdox Should detect a Store API request whose URL has repeated leading slashes.
	 */
	public function test_is_store_api_request_returns_true_for_repeated_slashes(): void {
		$_SERVER['REQUEST_URI'] = '///wp-json/wc/store/v1/cart';

		$this->assertTrue( WC()->is_store_api_request(), 'A ///wp-json/wc/store/ path with repeated leading slashes should be detected as Store API.' );
	}

	/**
	 * @testdox Should detect a Store API plain-permalink request whose route has repeated leading slashes.
	 */
	public function test_is_store_api_request_returns_true_for_repeated_slashes_plain(): void {
		$_SERVER['REQUEST_URI'] = '/index.php?rest_route=//wc/store/v1/cart';
		$_GET['rest_route']     = '//wc/store/v1/cart';

		$this->assertTrue( WC()->is_store_api_request(), 'A ?rest_route=//wc/store/ request with repeated leading slashes should be detected as Store API.' );
	}

	/**
	 * @testdox Settings registration is hooked to both admin_init and rest_api_init to support direct PHP and REST consumption.
	 */
	public function test_register_wp_admin_settings_hooked_to_admin_init_and_rest_api_init(): void {
		// admin_init runs last so extensions registering settings pages or email classes on
		// admin_init are still captured; see https://github.com/woocommerce/woocommerce/pull/67494.
		$this->assertSame( 999, has_action( 'admin_init', array( WC(), 'register_wp_admin_settings' ) ) );
		$this->assertSame( 10, has_action( 'rest_api_init', array( WC(), 'register_wp_admin_settings' ) ) );
	}

	/**
	 * @testdox Settings registration is idempotent, so no hook ordering duplicates the settings groups.
	 */
	public function test_register_wp_admin_settings_is_idempotent(): void {
		remove_all_filters( 'woocommerce_settings_groups' );

		WC()->register_wp_admin_settings();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Reading the registered groups under test.
		$after_first = apply_filters( 'woocommerce_settings_groups', array() );

		WC()->register_wp_admin_settings();
		WC()->register_wp_admin_settings();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Reading the registered groups under test.
		$after_repeats = apply_filters( 'woocommerce_settings_groups', array() );

		$this->assertNotEmpty( $after_first, 'The first call should register the settings groups.' );
		$this->assertSame(
			count( $after_first ),
			count( $after_repeats ),
			'Repeat calls should not add duplicate settings groups.'
		);

		$ids = array_column( $after_repeats, 'id' );
		$this->assertSame( array_values( array_unique( $ids ) ), array_values( $ids ), 'Settings group ids should be unique.' );
	}

	/**
	 * @testdox Settings registration is not conditional on the hook it runs from.
	 */
	public function test_register_wp_admin_settings_does_not_depend_on_hook_context(): void {
		global $wp_current_filter, $wp_actions;

		// The previous guard keyed off doing_action( 'rest_api_init' ) && did_action( 'admin_init' ),
		// which made the rest_api_init path unreachable on admin requests. Reproduce that exact state
		// rather than calling the method bare: admin_init has already run, and registration is now
		// invoked from inside rest_api_init. Setting the globals doing_action()/did_action() read is
		// enough, and avoids firing every core callback bound to those two hooks.
		$current_filter_backup = $wp_current_filter;
		$admin_init_backup     = $wp_actions['admin_init'] ?? null;

		$wp_actions['admin_init'] = ( $admin_init_backup ?? 0 ) + 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_current_filter[]      = 'rest_api_init'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		try {
			$this->assertTrue( doing_action( 'rest_api_init' ), 'The removed guard condition should be reproduced.' );
			$this->assertNotEmpty( did_action( 'admin_init' ), 'The removed guard condition should be reproduced.' );

			remove_all_filters( 'woocommerce_settings_groups' );

			WC()->register_wp_admin_settings();

			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Reading the registered groups under test.
			$groups = apply_filters( 'woocommerce_settings_groups', array() );
			$ids    = array_column( $groups, 'id' );
		} finally {
			$wp_current_filter = $current_filter_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			if ( null === $admin_init_backup ) {
				unset( $wp_actions['admin_init'] );
			} else {
				$wp_actions['admin_init'] = $admin_init_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		$this->assertContains( 'general', $ids, 'Registration should happen regardless of which hook is running.' );
	}

	/**
	 * @testdox Settings registration recovers from a hook state reset without duplicating per-group settings.
	 */
	public function test_register_wp_admin_settings_recovers_from_hook_reset(): void {
		WC()->register_wp_admin_settings();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WordPress.NamingConventions.ValidHookName.UseUnderscores -- Reading the registered settings under test; the hook name is a core legacy one.
		$general_before = apply_filters( 'woocommerce_settings-general', array() );

		// Wipe the groups filter only, leaving the per-group filters attached. This mirrors
		// what the WP test framework does between tests (hook state restored, singletons kept).
		remove_all_filters( 'woocommerce_settings_groups' );
		WC()->register_wp_admin_settings();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Reading the registered groups under test.
		$groups = apply_filters( 'woocommerce_settings_groups', array() );
		$this->assertContains( 'general', array_column( $groups, 'id' ), 'Registration should re-attach after hook state is reset.' );

		$ids = array_column( $groups, 'id' );
		$this->assertSame( array_values( array_unique( $ids ) ), array_values( $ids ), 'Settings group ids should be unique after re-registration.' );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment, WordPress.NamingConventions.ValidHookName.UseUnderscores -- Reading the registered settings under test; the hook name is a core legacy one.
		$general_after = apply_filters( 'woocommerce_settings-general', array() );
		$this->assertNotEmpty( $general_before, 'The general settings group should have settings registered.' );
		$this->assertSame(
			count( $general_before ),
			count( $general_after ),
			'Per-group settings should not be duplicated by re-registration.'
		);
	}

	/**
	 * @testdox Should load WooCommerce includes in post editor load actions.
	 */
	public function test_loads_woocommerce_includes_for_post_editor_load_actions(): void {
		$this->assertSame(
			10,
			has_action( 'load-post.php', array( WC(), 'includes' ) ),
			'Existing post editor requests should invoke WooCommerce includes before block rendering.'
		);
		$this->assertSame(
			10,
			has_action( 'load-post-new.php', array( WC(), 'includes' ) ),
			'New post editor requests should invoke WooCommerce includes before block rendering.'
		);

		$original_query     = WC()->query;
		$original_screen    = $GLOBALS['current_screen'] ?? null;
		WC()->query         = null;
		$query_after_action = null;
		set_current_screen( 'post' );

		try {
			$this->assertTrue( is_admin(), 'New post editor load action should run in an admin context.' );
			do_action( 'load-post-new.php' );
			$query_after_action = WC()->query;
		} finally {
			WC()->query                = $original_query;
			$GLOBALS['current_screen'] = $original_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$this->assertInstanceOf(
			WC_Query::class,
			$query_after_action,
			'New post editor load action should invoke WooCommerce includes.'
		);
		$this->assertTrue(
			function_exists( 'wc_set_notices' ),
			'New post editor load action should load frontend includes such as wc-notice-functions.php.'
		);
	}
}
