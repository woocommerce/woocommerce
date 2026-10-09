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
	 * @param WC_Shipping_Rate $rate Rate object.
	 * @return array
	 */
	private function invoke_get_rate_response( WC_Shipping_Rate $rate ): array {
		$reflection = new ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'get_rate_response' );
		$method->setAccessible( true );

		return $method->invoke( $this->sut, $rate );
	}
}
