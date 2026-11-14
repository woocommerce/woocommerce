<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartShippingRateSchema;
use ReflectionClass;
use WC_Shipping_Rate;
use WC_Unit_Test_Case;

/**
 * Tests for the CartShippingRateSchema class.
 */
class CartShippingRateSchemaTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var CartShippingRateSchema
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$formatters = new Formatters();
		$formatters->register( 'currency', CurrencyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'money', MoneyFormatter::class );

		$extend     = new ExtendSchema( $formatters );
		$controller = new SchemaController( $extend );

		$this->sut = $controller->get( CartShippingRateSchema::IDENTIFIER );
	}

	/**
	 * @testdox Shipping rate responses use the cart's per-rate tax rounding.
	 * @testWith ["yes", "no", 0, [10.5], "10", false]
	 *           ["yes", "yes", 0, [10.5], "11", false]
	 *           ["yes", "no", 0, [5.5, 5.5], "10", false]
	 *           ["yes", "yes", 0, [5.5, 5.5], "11", false]
	 *           ["yes", "no", 2, [0.105], "10", false]
	 *           ["no", "no", 2, [0.105], "11", false]
	 *           ["yes", "no", 0, [], "0", false]
	 *           ["yes", "no", 0, [10.5], "11", true]
	 *
	 * @param string $prices_include_tax Whether product prices include tax.
	 * @param string $round_at_subtotal  Whether tax rounds at subtotal.
	 * @param int    $decimals           Currency decimals.
	 * @param array  $taxes              Raw rate taxes.
	 * @param string $expected           Expected tax in minor units.
	 * @param bool   $round_up           Whether a tax filter selects half up.
	 */
	public function test_rate_response_rounds_each_tax( string $prices_include_tax, string $round_at_subtotal, int $decimals, array $taxes, string $expected, bool $round_up ): void {
		update_option( 'woocommerce_prices_include_tax', $prices_include_tax );
		update_option( 'woocommerce_tax_round_at_subtotal', $round_at_subtotal );
		update_option( 'woocommerce_price_num_decimals', $decimals );
		if ( $round_up ) {
			add_filter(
				'wc_round_tax_total',
				static function ( $rounded, $value, $precision ) {
					return round( $value, $precision, PHP_ROUND_HALF_UP );
				},
				10,
				3
			);
		}
		$rate = new WC_Shipping_Rate( 'flat_rate:1', 'Flat rate', 70, $taxes, 'flat_rate' );
		foreach ( array( '', 'flat_rate:1' ) as $selected_rate ) {
			$response = $this->invoke_get_rate_response( $rate, $selected_rate );
			$this->assertSame( $expected, $response['taxes'] );
			$this->assertSame( 'flat_rate:1' === $selected_rate, $response['selected'] );
		}
		$this->assertSame( $taxes, $rate->get_taxes(), 'Formatting must not change cached rate taxes.' );
	}

	/**
	 * @testdox Rate responses preserve the existing raw-array filter contract.
	 */
	public function test_rate_response_preserves_tax_filters(): void {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		update_option( 'woocommerce_price_num_decimals', 0 );
		$rate = new WC_Shipping_Rate( 'flat_rate:1', 'Flat rate', 70, array( 10.5 ), 'flat_rate' );
		add_filter(
			'woocommerce_shipping_rate_taxes',
			function ( $taxes, $filtered_rate ) use ( $rate ) {
				$this->assertSame( array( 10.5 ), $taxes );
				$this->assertSame( $rate, $filtered_rate );
				return array( 5.5, 5.5 );
			},
			10,
			2
		);
		$shipping_tax_filter_calls = did_filter( 'woocommerce_get_shipping_tax' );
		$response                  = $this->invoke_get_rate_response( $rate );

		$this->assertSame( '10', $response['taxes'] );
		$this->assertSame( $shipping_tax_filter_calls, did_filter( 'woocommerce_get_shipping_tax' ), 'Store API rate responses must not acquire a classic-only tax filter.' );
	}

	/**
	 * @testdox Should exclude hidden shipping rate meta from API response.
	 */
	public function test_get_rate_response_excludes_hidden_meta_data(): void {
		$rate = new WC_Shipping_Rate( 'pickup_location:0', 'Pickup', 0, array(), 'pickup_location' );
		$rate->add_meta_data( 'pickup_location', 'Main store' );
		$rate->add_meta_data(
			'_pickup_location_address',
			array(
				'country'  => 'GB',
				'state'    => '',
				'postcode' => 'PR1 4SS',
				'city'     => 'Preston',
			)
		);
		$rate->add_meta_data( '_private_note', 'Internal only' );

		$response = $this->invoke_get_rate_response( $rate );

		$this->assertSame(
			array(
				array(
					'key'   => 'pickup_location',
					'value' => 'Main store',
				),
			),
			$response['meta_data'],
			'Only public shipping rate meta should be exposed by the Store API.'
		);
	}

	/**
	 * @testdox Should return package destination addresses as sanitized plain text.
	 */
	public function test_package_destination_uses_plain_text_values(): void {
		$package = array(
			'destination' => array(
				'address_1' => '1 Rock & Roll <script>alert("x")</script>',
				'address_2' => '',
				'city'      => 'Beverly Hills',
				'state'     => 'CA',
				'postcode'  => '90210',
				'country'   => 'US',
			),
		);

		$method = ( new ReflectionClass( $this->sut ) )->getMethod( 'prepare_package_destination_response' );
		$method->setAccessible( true );
		$response = $method->invoke( $this->sut, $package );

		$this->assertSame( '1 Rock & Roll', $response->address_1 );
	}

	/**
	 * Invoke the protected get_rate_response method.
	 *
	 * @param WC_Shipping_Rate $rate          Rate object.
	 * @param string           $selected_rate Selected rate ID.
	 * @return array
	 */
	private function invoke_get_rate_response( WC_Shipping_Rate $rate, string $selected_rate = '' ): array {
		$reflection = new ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'get_rate_response' );
		$method->setAccessible( true );

		return $method->invoke( $this->sut, $rate, $selected_rate );
	}
}
