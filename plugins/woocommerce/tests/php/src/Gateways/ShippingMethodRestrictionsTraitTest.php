<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Gateways;

use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Payment_Gateway;
use WC_Shipping_Rate;
use WC_Shipping_Zone;
use WC_Unit_Test_Case;

/**
 * Tests for the ShippingMethodRestrictionsTrait, through the offline gateways that use it.
 */
class ShippingMethodRestrictionsTraitTest extends WC_Unit_Test_Case {

	/**
	 * The real cart, restored after each test.
	 *
	 * @var mixed
	 */
	private $original_cart;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_cart = WC()->cart;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			WC()->cart = $this->original_cart;
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Data provider with the gateway classes using the trait.
	 *
	 * @return array
	 */
	public function gateway_classes(): array {
		return array(
			'cod'    => array( WC_Gateway_COD::class ),
			'bacs'   => array( WC_Gateway_BACS::class ),
			'cheque' => array( WC_Gateway_Cheque::class ),
		);
	}

	/**
	 * @testdox Should add the shipping method restriction fields to the gateway settings form.
	 * @dataProvider gateway_classes
	 *
	 * @param string $gateway_class Gateway class name.
	 */
	public function test_form_fields_include_shipping_method_restrictions( string $gateway_class ): void {
		$form_fields = ( new $gateway_class() )->get_form_fields();

		$this->assertSame( 'multiselect', $form_fields['enable_for_methods']['type'] ?? null, "$gateway_class should have the enable_for_methods multiselect" );
		$this->assertSame( 'checkbox', $form_fields['enable_for_virtual']['type'] ?? null, "$gateway_class should have the enable_for_virtual checkbox" );
		$this->assertSame( 'yes', $form_fields['enable_for_virtual']['default'], 'Virtual orders should be accepted by default' );
	}

	/**
	 * @testdox Should default to no restriction when the gateway settings were saved before the fields existed.
	 * @dataProvider gateway_classes
	 *
	 * @param string $gateway_class Gateway class name.
	 */
	public function test_defaults_to_no_restriction_for_previously_saved_settings( string $gateway_class ): void {
		$gateway = $this->create_gateway(
			$gateway_class,
			array(
				'enabled' => 'yes',
				'title'   => 'Offline payment',
			)
		);
		$this->fake_cart( array( $this->create_rate( 'free_shipping', 3 ) ) );

		$this->assertSame( array(), $gateway->enable_for_methods, 'No shipping method restriction should be applied' );
		$this->assertTrue( $gateway->enable_for_virtual, 'Virtual orders should be accepted' );
		$this->assertTrue( $gateway->is_available(), 'The gateway should be available for any shipping method' );
	}

	/**
	 * @testdox Should only be available when a selected shipping method matches the restriction.
	 * @dataProvider availability_scenarios
	 *
	 * @param array  $enable_for_methods Restriction saved in the settings.
	 * @param string $method_id          Selected shipping method id.
	 * @param int    $instance_id        Selected shipping method instance id.
	 * @param bool   $expected           Expected availability.
	 */
	public function test_is_available_respects_shipping_method_restrictions( array $enable_for_methods, string $method_id, int $instance_id, bool $expected ): void {
		$gateway = $this->create_gateway(
			WC_Gateway_BACS::class,
			array(
				'enabled'            => 'yes',
				'enable_for_methods' => $enable_for_methods,
				'enable_for_virtual' => 'yes',
			)
		);
		$this->fake_cart( array( $this->create_rate( $method_id, $instance_id ) ) );

		$this->assertSame( $expected, $gateway->is_available() );
	}

	/**
	 * Data provider for the shipping method restriction scenarios.
	 *
	 * @return array
	 */
	public function availability_scenarios(): array {
		return array(
			'matching instance'          => array( array( 'flat_rate:1' ), 'flat_rate', 1, true ),
			'other instance'             => array( array( 'flat_rate:1' ), 'flat_rate', 2, false ),
			'other method'               => array( array( 'flat_rate:1' ), 'free_shipping', 1, false ),
			'any instance of the method' => array( array( 'flat_rate' ), 'flat_rate', 7, true ),
			'one of several'             => array( array( 'local_pickup:4', 'flat_rate:1' ), 'flat_rate', 1, true ),
		);
	}

	/**
	 * @testdox Should honor the "Accept for virtual orders" setting when the cart needs no shipping.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $enable_for_virtual Saved setting value.
	 * @param bool   $expected           Expected availability.
	 */
	public function test_is_available_respects_enable_for_virtual( string $enable_for_virtual, bool $expected ): void {
		$gateway = $this->create_gateway(
			WC_Gateway_Cheque::class,
			array(
				'enabled'            => 'yes',
				'enable_for_methods' => array( 'flat_rate:1' ),
				'enable_for_virtual' => $enable_for_virtual,
			)
		);
		$this->fake_cart( array(), false );

		$this->assertSame( $expected, $gateway->is_available() );
	}

	/**
	 * @testdox Should not consult the cart when the gateway is disabled.
	 * @dataProvider gateway_classes
	 *
	 * @param string $gateway_class Gateway class name.
	 */
	public function test_is_available_returns_false_when_disabled( string $gateway_class ): void {
		$gateway = $this->create_gateway( $gateway_class, array( 'enabled' => 'no' ) );
		$cart    = $this->fake_cart( array( $this->create_rate( 'flat_rate', 1 ) ) );

		$this->assertFalse( $gateway->is_available() );
		$this->assertSame( 0, $cart->needs_shipping_call_count, 'The cart should not be queried for disabled gateways' );
	}

	/**
	 * @testdox Should load the shipping method options only when the gateway settings page is requested.
	 * @dataProvider gateway_classes
	 *
	 * @param string $gateway_class Gateway class name.
	 */
	public function test_shipping_method_options_load_only_on_settings_page( string $gateway_class ): void {
		$this->assertEmpty( ( new $gateway_class() )->get_form_fields()['enable_for_methods']['options'], 'Options should not be loaded outside the settings page' );

		set_current_screen( 'woocommerce_page_wc-settings' );
		$_REQUEST['page']    = 'wc-settings';
		$_REQUEST['tab']     = 'checkout';
		$_REQUEST['section'] = $gateway_class::ID;

		try {
			$options = ( new $gateway_class() )->get_form_fields()['enable_for_methods']['options'];
		} finally {
			$GLOBALS['current_screen'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			unset( $_REQUEST['page'], $_REQUEST['tab'], $_REQUEST['section'] );
		}

		$this->assertNotEmpty( $options, 'Options should be loaded on the gateway settings page' );
	}

	/**
	 * @testdox Should share the shipping method options between gateways for the rest of the request and refresh them when a zone changes.
	 */
	public function test_shipping_method_options_are_cached_per_request_and_invalidated_on_zone_change(): void {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Cached zone' );
		$zone->save();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );

		$cod_options = ( new WC_Gateway_COD() )->get_shipping_method_options();
		$this->assertArrayHasKey( 'flat_rate:' . $instance_id, $cod_options['Flat rate'] );

		$zone_query_count = 0;
		$count_queries    = function ( $query ) use ( &$zone_query_count ) {
			if ( false !== strpos( $query, 'woocommerce_shipping_zone_methods' ) ) {
				++$zone_query_count;
			}
			return $query;
		};
		add_filter( 'query', $count_queries );

		try {
			$bacs_options = ( new WC_Gateway_BACS() )->get_shipping_method_options();
		} finally {
			remove_filter( 'query', $count_queries );
		}

		$this->assertSame( $cod_options, $bacs_options );
		$this->assertSame( 0, $zone_query_count, 'Another gateway should reuse the options loaded earlier in the request' );

		$new_instance_id = $zone->add_shipping_method( 'free_shipping' );

		$this->assertArrayHasKey( 'free_shipping:' . $new_instance_id, ( new WC_Gateway_Cheque() )->get_shipping_method_options()['Free shipping'], 'Changing a zone should refresh the options' );
	}

	/**
	 * Save the given settings for a gateway and instantiate it.
	 *
	 * @param string $gateway_class Gateway class name.
	 * @param array  $settings      Settings to save.
	 * @return WC_Payment_Gateway
	 */
	private function create_gateway( string $gateway_class, array $settings ): WC_Payment_Gateway {
		update_option( 'woocommerce_' . $gateway_class::ID . '_settings', $settings );

		return new $gateway_class();
	}

	/**
	 * Create a shipping rate for the given method and instance.
	 *
	 * @param string $method_id   Shipping method id.
	 * @param int    $instance_id Shipping method instance id.
	 * @return WC_Shipping_Rate
	 */
	private function create_rate( string $method_id, int $instance_id ): WC_Shipping_Rate {
		return new WC_Shipping_Rate( "$method_id:$instance_id", 'Shipping', 10, array(), $method_id, $instance_id );
	}

	/**
	 * Replace WC()->cart with a minimal fake exposing the selected shipping methods.
	 *
	 * @param WC_Shipping_Rate[] $shipping_methods Selected shipping methods.
	 * @param bool               $needs_shipping   Whether the cart needs shipping.
	 * @return object The fake cart.
	 */
	private function fake_cart( array $shipping_methods, bool $needs_shipping = true ) {
		WC()->cart = new class( $shipping_methods, $needs_shipping ) {
			/**
			 * Cart total, read by WC_Payment_Gateway::get_order_total().
			 *
			 * @var float
			 */
			public $total = 10.0;

			/**
			 * Number of times needs_shipping() was called.
			 *
			 * @var int
			 */
			public $needs_shipping_call_count = 0;

			/**
			 * Selected shipping methods.
			 *
			 * @var WC_Shipping_Rate[]
			 */
			private $shipping_methods;

			/**
			 * Whether the cart needs shipping.
			 *
			 * @var bool
			 */
			private $needs_shipping;

			/**
			 * Constructor.
			 *
			 * @param WC_Shipping_Rate[] $shipping_methods Selected shipping methods.
			 * @param bool               $needs_shipping   Whether the cart needs shipping.
			 */
			public function __construct( array $shipping_methods, bool $needs_shipping ) {
				$this->shipping_methods = $shipping_methods;
				$this->needs_shipping   = $needs_shipping;
			}

			/**
			 * Whether the cart needs shipping.
			 *
			 * @return bool
			 */
			public function needs_shipping(): bool {
				++$this->needs_shipping_call_count;
				return $this->needs_shipping;
			}

			/**
			 * Selected shipping methods.
			 *
			 * @return WC_Shipping_Rate[]
			 */
			public function get_shipping_methods(): array {
				return $this->shipping_methods;
			}
		};

		return WC()->cart;
	}
}
