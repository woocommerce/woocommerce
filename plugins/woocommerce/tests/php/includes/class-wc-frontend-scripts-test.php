<?php
declare( strict_types=1 );

/**
 * Tests for WC_Frontend_Scripts.
 *
 * @package WooCommerce\Tests\FrontendScripts
 */

/**
 * Class WC_Frontend_Scripts_Test.
 */
class WC_Frontend_Scripts_Test extends WC_Unit_Test_Case {

	/**
	 * Gateways filter callback reference for cleanup.
	 *
	 * @var callable|null
	 */
	private $gateways_filter_callback = null;

	/**
	 * Setup test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Set jetpack_activation_source option to prevent "Cannot use bool as array" error.
		update_option( 'jetpack_activation_source', array( '', '' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Remove the gateways filter if it was added.
		if ( null !== $this->gateways_filter_callback ) {
			remove_filter( 'woocommerce_payment_gateways', $this->gateways_filter_callback );
			$this->gateways_filter_callback = null;
		}

		// Reinitialize payment gateways to clean state.
		WC()->payment_gateways()->init();

		delete_option( 'jetpack_activation_source' );
	}

	/**
	 * Create a mock gateway with custom place order button support.
	 *
	 * @return WC_Payment_Gateway
	 */
	private function create_gateway_with_custom_button(): WC_Payment_Gateway {
		return new class() extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id                            = 'mock_custom_button';
				$this->enabled                       = 'yes';
				$this->method_title                  = 'Mock Gateway With Custom Button';
				$this->has_custom_place_order_button = true;
			}
		};
	}

	/**
	 * Create a mock gateway without custom place order button.
	 *
	 * @return WC_Payment_Gateway
	 */
	private function create_gateway_without_custom_button(): WC_Payment_Gateway {
		return new class() extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id           = 'mock_no_custom_button';
				$this->enabled      = 'yes';
				$this->method_title = 'Mock Gateway Without Custom Button';
			}
		};
	}

	/**
	 * Create a mock gateway with truthy but non-boolean value.
	 *
	 * @return WC_Payment_Gateway
	 */
	private function create_gateway_with_truthy_value(): WC_Payment_Gateway {
		return new class() extends WC_Payment_Gateway {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id                            = 'mock_truthy_value';
				$this->enabled                       = 'yes';
				$this->method_title                  = 'Mock Gateway With Truthy Value';
				$this->has_custom_place_order_button = 'yes'; // Truthy but not boolean true.
			}
		};
	}

	/**
	 * Helper to register test gateway instances.
	 *
	 * @param array $gateway_instances Array of gateway instances to register.
	 */
	private function register_test_gateways( array $gateway_instances ): void {
		$this->gateways_filter_callback = function ( $gateways ) use ( $gateway_instances ) {
			return array_merge( $gateways, $gateway_instances );
		};
		add_filter( 'woocommerce_payment_gateways', $this->gateways_filter_callback );
		WC()->payment_gateways()->init();
	}

	/**
	 * Helper to call private static method get_script_data via Reflection.
	 *
	 * @param string $handle Script handle.
	 * @return array|bool Script data array or false.
	 */
	private function get_script_data( string $handle ) {
		$reflection = new ReflectionClass( 'WC_Frontend_Scripts' );
		$method     = $reflection->getMethod( 'get_script_data' );
		$method->setAccessible( true );
		return $method->invoke( null, $handle );
	}

	/**
	 * Test that script data for wc-checkout includes gateways_with_custom_place_order_button key.
	 */
	public function test_checkout_script_data_includes_gateways_with_custom_place_order_button_key(): void {
		$data = $this->get_script_data( 'wc-checkout' );

		$this->assertArrayHasKey( 'gateways_with_custom_place_order_button', $data );
		$this->assertIsArray( $data['gateways_with_custom_place_order_button'] );
	}

	/**
	 * Test that script data for wc-add-payment-method includes gateways_with_custom_place_order_button key.
	 */
	public function test_add_payment_method_script_data_includes_gateways_with_custom_place_order_button_key(): void {
		$data = $this->get_script_data( 'wc-add-payment-method' );

		$this->assertArrayHasKey( 'gateways_with_custom_place_order_button', $data );
		$this->assertIsArray( $data['gateways_with_custom_place_order_button'] );
	}

	/**
	 * Test that gateways with has_custom_place_order_button = true are included.
	 */
	public function test_gateway_with_custom_button_is_included(): void {
		$this->register_test_gateways( array( $this->create_gateway_with_custom_button() ) );

		$data = $this->get_script_data( 'wc-checkout' );

		$this->assertContains( 'mock_custom_button', $data['gateways_with_custom_place_order_button'] );
	}

	/**
	 * Test that gateways without has_custom_place_order_button are not included.
	 */
	public function test_gateway_without_custom_button_is_not_included(): void {
		$this->register_test_gateways( array( $this->create_gateway_without_custom_button() ) );

		$data = $this->get_script_data( 'wc-checkout' );

		$this->assertNotContains( 'mock_no_custom_button', $data['gateways_with_custom_place_order_button'] );
	}

	/**
	 * Test that gateways with truthy but non-boolean values are not included.
	 *
	 * The has_custom_place_order_button property must be strictly boolean true,
	 * not just truthy values like 'yes', '1', or 1.
	 */
	public function test_gateway_with_truthy_non_boolean_value_is_not_included(): void {
		$this->register_test_gateways( array( $this->create_gateway_with_truthy_value() ) );

		$data = $this->get_script_data( 'wc-checkout' );

		$this->assertNotContains( 'mock_truthy_value', $data['gateways_with_custom_place_order_button'] );
	}

	/**
	 * Test that multiple gateways with custom buttons are all included.
	 */
	public function test_multiple_gateways_with_custom_buttons_are_included(): void {
		$this->register_test_gateways(
			array(
				$this->create_gateway_with_custom_button(),
				$this->create_gateway_without_custom_button(),
				$this->create_gateway_with_truthy_value(),
			)
		);

		$data = $this->get_script_data( 'wc-checkout' );

		// Only the gateway with true boolean should be included.
		$this->assertContains( 'mock_custom_button', $data['gateways_with_custom_place_order_button'] );
		$this->assertNotContains( 'mock_no_custom_button', $data['gateways_with_custom_place_order_button'] );
		$this->assertNotContains( 'mock_truthy_value', $data['gateways_with_custom_place_order_button'] );
	}

	/**
	 * Test that the same gateways are returned for both checkout and add-payment-method.
	 */
	public function test_same_gateways_returned_for_checkout_and_add_payment_method(): void {
		$this->register_test_gateways( array( $this->create_gateway_with_custom_button() ) );

		$checkout_data           = $this->get_script_data( 'wc-checkout' );
		$add_payment_method_data = $this->get_script_data( 'wc-add-payment-method' );

		$this->assertEquals(
			$checkout_data['gateways_with_custom_place_order_button'],
			$add_payment_method_data['gateways_with_custom_place_order_button']
		);
	}

	/**
	 * Test that default WooCommerce gateways are not in the list.
	 *
	 * Default gateways like BACS, COD, Cheque don't have custom place order buttons.
	 */
	public function test_default_gateways_are_not_in_list(): void {
		$data = $this->get_script_data( 'wc-checkout' );

		$this->assertNotContains( 'bacs', $data['gateways_with_custom_place_order_button'] );
		$this->assertNotContains( 'cod', $data['gateways_with_custom_place_order_button'] );
		$this->assertNotContains( 'cheque', $data['gateways_with_custom_place_order_button'] );
	}

	/**
	 * Invoke WC_Frontend_Scripts::register_scripts() and return the script definitions.
	 *
	 * @return array
	 */
	private function register_frontend_scripts(): array {
		$reflection = new ReflectionClass( 'WC_Frontend_Scripts' );

		$register = $reflection->getMethod( 'register_scripts' );
		$register->setAccessible( true );
		$register->invoke( null );

		$get_scripts = $reflection->getMethod( 'get_scripts' );
		$get_scripts->setAccessible( true );

		return $get_scripts->invoke( null );
	}

	/**
	 * Test that scripts with legacy alias handles stay deferred and footer-printed.
	 */
	public function test_legacy_handle_scripts_keep_defer_strategy(): void {
		$scripts = $this->register_frontend_scripts();

		foreach ( $scripts as $name => $props ) {
			if ( ! isset( $props['legacy_handle'] ) ) {
				continue;
			}

			$this->assertSame( 'defer', wp_scripts()->get_data( $name, 'strategy' ), "Real handle '{$name}' should keep the defer loading strategy." );
			$this->assertSame( 1, wp_scripts()->get_data( $name, 'group' ), "Real handle '{$name}' should be printed in the footer." );
		}
	}

	/**
	 * Test that a blocking script registered against a legacy alias after the head
	 * is printed still executes after the handle it depends on.
	 */
	public function test_late_blocking_dependent_downgrades_legacy_handle(): void {
		$this->register_frontend_scripts();
		wp_enqueue_script( 'wc-cart' );

		ob_start();
		wp_scripts()->do_head_items();
		ob_end_clean();

		// A gateway enqueues its blocking script only once the head has been printed.
		wp_register_script( 'test-gateway-form', 'https://example.com/gateway.js', array( 'jquery-payment' ), '1', array( 'in_footer' => true ) );
		wp_enqueue_script( 'test-gateway-form' );

		ob_start();
		wp_scripts()->do_footer_items();
		$footer = ob_get_clean();

		$payment_position = strpos( $footer, 'id="wc-jquery-payment-js"' );
		$gateway_position = strpos( $footer, 'id="test-gateway-form-js"' );

		$this->assertNotFalse( $payment_position, 'wc-jquery-payment should be printed in the footer.' );
		$this->assertNotFalse( $gateway_position, 'The gateway script should be printed in the footer.' );
		$this->assertLessThan( $gateway_position, $payment_position, 'wc-jquery-payment should be printed before the gateway script that depends on it.' );

		preg_match( '#<script([^>]*)id="wc-jquery-payment-js"#', $footer, $matches );
		$this->assertStringNotContainsString( ' defer', $matches[1], 'wc-jquery-payment should be downgraded to blocking when a blocking script depends on its legacy alias.' );

		// Only the affected handle is downgraded; the rest of the chain stays deferred.
		preg_match( '#<script([^>]*)id="wc-cart-js"#', $footer, $cart_matches );
		$this->assertStringContainsString( ' defer', $cart_matches[1], 'wc-cart should remain deferred.' );
	}
}
