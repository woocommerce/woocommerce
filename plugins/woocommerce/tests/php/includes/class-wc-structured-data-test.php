<?php
declare( strict_types = 1 );

/**
 * Class WC_Structured_Data_Test.
 */
class WC_Structured_Data_Test extends \WC_Unit_Test_Case {

	/** @var WC_Structured_Data $structured_data */
	public $structured_data;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		include_once WC_ABSPATH . 'includes/class-wc-structured-data.php';
		$this->structured_data = new WC_Structured_Data();
		parent::setUp();
	}

	/**
	 * Test is_valid_gtin function
	 *
	 * @return void
	 */
	public function test_is_valid_gtin(): void {

		$valid_gtins = array(
			'12345678',
			'123456789012',
			'1234567890123',
			'12345678901234',
		);

		$invalid_gtins = array(
			'',
			null,
			false,
			12345678,
			123.4e-5,
			+1234567,
			'abcdefgh',
			'-9999999',
			'12-45-66',
			'123',
			'123456789012345',
			'123456789',
			'1234567890',
			'12 34 56 78',
			'12 34 56',
			'+12345678',
			'123.4e-5',
		);

		foreach ( $valid_gtins as $valid_gtin ) {
			$this->assertTrue( $this->structured_data->is_valid_gtin( $valid_gtin ) );
		}

		foreach ( $invalid_gtins as $invalid_gtin ) {
			$this->assertFalse( $this->structured_data->is_valid_gtin( $invalid_gtin ) );
		}
	}

	/**
	 * Test prepare_gtin function
	 *
	 * @return void
	 */
	public function test_prepare_gtin(): void {
		$this->assertEquals( $this->structured_data->prepare_gtin( '123-456-78' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( '-123-456-78' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( 'GTIN: 123-456-78' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( '123 456 78' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( null ), '' );
		$this->assertEquals( $this->structured_data->prepare_gtin( 'GTIN' ), '' );
		$this->assertEquals( $this->structured_data->prepare_gtin( 123 ), '' );
		$this->assertEquals( $this->structured_data->prepare_gtin( array( '123-456-78', '123-456-78' ) ), '' );
		$this->assertEquals( $this->structured_data->prepare_gtin( '+12345678' ), '12345678' );
		$this->assertEquals( $this->structured_data->prepare_gtin( '123.4e-5' ), '12345' );
	}

	/**
	 * Test simple product offer structured data includes offer-level price currency.
	 *
	 * @return void
	 */
	public function test_simple_product_offer_includes_offer_level_price_currency(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '97' );
		$product->set_price( '97' );
		$product->save();

		$this->structured_data->generate_product_data( $product );

		$data  = $this->structured_data->get_data();
		$offer = $data[0]['offers'][0];

		$this->assertEquals( '97.00', $offer['price'] );
		$this->assertEquals( get_woocommerce_currency(), $offer['priceCurrency'] );
		$this->assertEquals( get_woocommerce_currency(), $offer['priceSpecification'][0]['priceCurrency'] );
	}

	/**
	 * Test on-sale simple product offer reports the sale price at the offer top level.
	 *
	 * @return void
	 */
	public function test_simple_product_offer_on_sale_uses_sale_price_at_offer_level(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '100' );
		$product->set_sale_price( '70' );
		$product->set_price( '70' );
		$product->save();

		$this->structured_data->generate_product_data( $product );

		$data  = $this->structured_data->get_data();
		$offer = $data[0]['offers'][0];

		// The offer-level `price` should reflect the sale price, matching `priceSpecification[0]['price']`.
		$this->assertEquals( '70.00', $offer['price'] );
		$this->assertEquals( '70.00', $offer['priceSpecification'][0]['price'] );
		$this->assertEquals( get_woocommerce_currency(), $offer['priceCurrency'] );
	}

	/**
	 * @testdox valueAddedTaxIncluded is present in product structured data when taxes are enabled.
	 *
	 * @return void
	 */
	public function test_product_structured_data_includes_vat_when_taxes_enabled(): void {
		// Enable taxes.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );

		// Create a simple product with a price.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		// Generate structured data.
		$this->structured_data->generate_product_data( $product );
		$data = $this->structured_data->get_data();

		// Get the first structured data entry.
		$this->assertNotEmpty( $data );
		$product_data = $data[0];

		// Check that offers exist.
		$this->assertArrayHasKey( 'offers', $product_data );
		$this->assertNotEmpty( $product_data['offers'] );

		// Get the first offer.
		$offer = $product_data['offers'][0];

		// Check that priceSpecification exists and contains valueAddedTaxIncluded.
		$this->assertArrayHasKey( 'priceSpecification', $offer );
		$this->assertNotEmpty( $offer['priceSpecification'] );

		$price_spec = $offer['priceSpecification'][0];
		$this->assertArrayHasKey( 'valueAddedTaxIncluded', $price_spec );
		$this->assertTrue( $price_spec['valueAddedTaxIncluded'] );

		// Clean up.
		$product->delete( true );
	}

	/**
	 * @testdox valueAddedTaxIncluded is not present in product structured data when taxes are disabled.
	 *
	 * @return void
	 */
	public function test_product_structured_data_excludes_vat_when_taxes_disabled(): void {
		// Disable taxes.
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Create a simple product with a price.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		// Generate structured data.
		$this->structured_data->generate_product_data( $product );
		$data = $this->structured_data->get_data();

		// Get the first structured data entry.
		$this->assertNotEmpty( $data );
		$product_data = $data[0];

		// Check that offers exist.
		$this->assertArrayHasKey( 'offers', $product_data );
		$this->assertNotEmpty( $product_data['offers'] );

		// Get the first offer.
		$offer = $product_data['offers'][0];

		// Check that priceSpecification exists.
		$this->assertArrayHasKey( 'priceSpecification', $offer );
		$this->assertNotEmpty( $offer['priceSpecification'] );

		$price_spec = $offer['priceSpecification'][0];

		// valueAddedTaxIncluded should not be present when taxes are disabled.
		$this->assertArrayNotHasKey( 'valueAddedTaxIncluded', $price_spec );

		// Clean up.
		$product->delete( true );
	}

	/**
	 * @testdox valueAddedTaxIncluded is present in order structured data when taxes are enabled.
	 *
	 * @return void
	 */
	public function test_order_structured_data_includes_vat_when_taxes_enabled(): void {
		// Enable taxes with prices inclusive of tax.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		// Create a simple product and order.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->save();

		// Generate structured data.
		$this->structured_data->generate_order_data( $order );
		$data = $this->structured_data->get_data();

		// Get the first structured data entry (should be order).
		$this->assertNotEmpty( $data );
		$order_data = $data[0];

		// Check that priceSpecification exists and contains valueAddedTaxIncluded as a boolean.
		$this->assertArrayHasKey( 'priceSpecification', $order_data );
		$this->assertArrayHasKey( 'valueAddedTaxIncluded', $order_data['priceSpecification'] );
		$this->assertIsBool( $order_data['priceSpecification']['valueAddedTaxIncluded'] );
		$this->assertTrue( $order_data['priceSpecification']['valueAddedTaxIncluded'] );

		// Clean up.
		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * @testdox valueAddedTaxIncluded is not present in order structured data when taxes are disabled.
	 *
	 * @return void
	 */
	public function test_order_structured_data_excludes_vat_when_taxes_disabled(): void {
		// Disable taxes.
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Create a simple product and order.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->save();

		// Generate structured data.
		$this->structured_data->generate_order_data( $order );
		$data = $this->structured_data->get_data();

		// Get the first structured data entry (should be order).
		$this->assertNotEmpty( $data );
		$order_data = $data[0];

		// Check that priceSpecification exists.
		$this->assertArrayHasKey( 'priceSpecification', $order_data );

		// valueAddedTaxIncluded should not be present when taxes are disabled.
		$this->assertArrayNotHasKey( 'valueAddedTaxIncluded', $order_data['priceSpecification'] );

		// Clean up.
		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * When a variable product page is requested for a single, fully-specified variation, the offer
	 * should describe that variation with a single Offer and exact price (no AggregateOffer range),
	 * and reference the parent product group.
	 *
	 * @return void
	 */
	public function test_variable_product_with_selected_variation_uses_single_offer(): void {
		$product = WC_Helper_Product::create_variation_product();
		WC_Product_Variable::sync( $product->get_id() );
		$product = wc_get_product( $product->get_id() );

		// Uniquely identifies the "huge / red / 0" variation priced at 16.
		$_GET['attribute_pa_size']   = 'huge';
		$_GET['attribute_pa_colour'] = 'red';
		$_GET['attribute_pa_number'] = '0';

		try {
			$this->structured_data->generate_product_data( $product );
			$data  = $this->structured_data->get_data();
			$offer = $data[0]['offers'][0];

			$this->assertEquals( 'Offer', $offer['@type'] );
			$this->assertEquals( '16.00', $offer['price'] );
			$this->assertArrayNotHasKey( 'lowPrice', $offer );
			$this->assertArrayNotHasKey( 'highPrice', $offer );
			$this->assertEquals( get_woocommerce_currency(), $offer['priceCurrency'] );

			// The variation is grouped under the parent product (Google `item_group_id`).
			$this->assertEquals( $product->get_sku(), $data[0]['inProductGroupWithID'] );
			// The variation's own SKU is used.
			$this->assertEquals( 'DUMMY SKU VARIABLE HUGE RED 0', $data[0]['sku'] );
		} finally {
			unset( $_GET['attribute_pa_size'], $_GET['attribute_pa_colour'], $_GET['attribute_pa_number'] );
		}
	}

	/**
	 * A GTIN uniquely identifies a single trade item, so a selected variation without its own GTIN
	 * must not inherit the parent product's GTIN.
	 *
	 * @return void
	 */
	public function test_variable_product_selected_variation_does_not_inherit_parent_gtin(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_global_unique_id( '12345678' );
		$product->save();
		WC_Product_Variable::sync( $product->get_id() );
		$product = wc_get_product( $product->get_id() );

		// Uniquely identifies the "huge / red / 0" variation, which has no GTIN of its own.
		$_GET['attribute_pa_size']   = 'huge';
		$_GET['attribute_pa_colour'] = 'red';
		$_GET['attribute_pa_number'] = '0';

		try {
			$this->structured_data->generate_product_data( $product );
			$data = $this->structured_data->get_data();

			$this->assertEquals( 'Offer', $data[0]['offers'][0]['@type'] );
			$this->assertArrayNotHasKey( 'gtin', $data[0] );
		} finally {
			unset( $_GET['attribute_pa_size'], $_GET['attribute_pa_colour'], $_GET['attribute_pa_number'] );
		}
	}

	/**
	 * Without a fully-specified variation selection, the variable product keeps the AggregateOffer
	 * price range (no behavior change).
	 *
	 * @return void
	 */
	public function test_variable_product_without_full_selection_uses_aggregate_offer(): void {
		$product = WC_Helper_Product::create_variation_product();
		WC_Product_Variable::sync( $product->get_id() );
		$product = wc_get_product( $product->get_id() );

		// Only one of the three variation attributes is provided: ambiguous, so fall back to aggregate.
		$_GET['attribute_pa_size'] = 'huge';

		try {
			$this->structured_data->generate_product_data( $product );
			$data  = $this->structured_data->get_data();
			$offer = $data[0]['offers'][0];

			$this->assertEquals( 'AggregateOffer', $offer['@type'] );
			$this->assertArrayHasKey( 'lowPrice', $offer );
			$this->assertArrayHasKey( 'highPrice', $offer );
			$this->assertArrayNotHasKey( 'inProductGroupWithID', $data[0] );
		} finally {
			unset( $_GET['attribute_pa_size'] );
		}
	}

	/**
	 * When a fully-specified selection matches more than one variation (a concrete variation and an
	 * overlapping "Any" variation), the price is ambiguous, so the parent AggregateOffer is kept.
	 *
	 * @return void
	 */
	public function test_variable_product_with_ambiguous_selection_uses_aggregate_offer(): void {
		$product = WC_Helper_Product::create_variation_product();
		WC_Product_Variable::sync( $product->get_id() );
		$product = wc_get_product( $product->get_id() );

		// Matches both "huge / blue / 2" and "huge / blue / any number".
		$_GET['attribute_pa_size']   = 'huge';
		$_GET['attribute_pa_colour'] = 'blue';
		$_GET['attribute_pa_number'] = '2';

		try {
			$this->structured_data->generate_product_data( $product );
			$data  = $this->structured_data->get_data();
			$offer = $data[0]['offers'][0];

			$this->assertEquals( 'AggregateOffer', $offer['@type'] );
			$this->assertArrayNotHasKey( 'inProductGroupWithID', $data[0] );
		} finally {
			unset( $_GET['attribute_pa_size'], $_GET['attribute_pa_colour'], $_GET['attribute_pa_number'] );
		}
	}

	/**
	 * A private variation matching the same selection must not block the single Offer for the
	 * published variation, because `find_matching_product_variation()` only resolves published ones.
	 *
	 * @return void
	 */
	public function test_variable_product_ignores_private_variation_when_counting_matches(): void {
		$product = WC_Helper_Product::create_variation_product();

		// Private sibling with the same attributes as the published "huge / red / 0" variation.
		$private_variation = new WC_Product_Variation();
		$private_variation->set_parent_id( $product->get_id() );
		$private_variation->set_attributes(
			array(
				'pa_size'   => 'huge',
				'pa_colour' => 'red',
				'pa_number' => '0',
			)
		);
		$private_variation->set_regular_price( 99 );
		$private_variation->set_status( 'private' );
		$private_variation->save();

		WC_Product_Variable::sync( $product->get_id() );
		$product = wc_get_product( $product->get_id() );

		$_GET['attribute_pa_size']   = 'huge';
		$_GET['attribute_pa_colour'] = 'red';
		$_GET['attribute_pa_number'] = '0';

		try {
			$this->structured_data->generate_product_data( $product );
			$data  = $this->structured_data->get_data();
			$offer = $data[0]['offers'][0];

			// Still a single Offer at the published variation's price, not the private one's.
			$this->assertEquals( 'Offer', $offer['@type'] );
			$this->assertEquals( '16.00', $offer['price'] );
		} finally {
			unset( $_GET['attribute_pa_size'], $_GET['attribute_pa_colour'], $_GET['attribute_pa_number'] );
		}
	}
}
