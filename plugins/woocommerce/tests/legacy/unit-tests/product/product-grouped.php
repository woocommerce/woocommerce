<?php
/**
 * Unit tests for the WC_Product_Grouped class.
 *
 * @package WooCommerce\Tests\Product
 */

/**
 * Class WC_Tests_Product_Grouped.
 */
class WC_Tests_Product_Grouped extends WC_Unit_Test_Case {

	/**
	 * Build a variable product with two variations at the given prices.
	 *
	 * @param float|int $low_price  Lower variation price.
	 * @param float|int $high_price Higher variation price.
	 * @return WC_Product_Variable
	 */
	private function create_variable_product_with_prices( $low_price, $high_price ) {
		$variable = new WC_Product_Variable();
		$variable->set_name( 'Variable ' . $low_price . '-' . $high_price );
		$variable->set_status( 'publish' );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'size' );
		$attribute->set_options( array( 'small', 'large' ) );
		$attribute->set_position( 0 );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$variable->set_attributes( array( $attribute ) );
		$variable->save();

		$low = new WC_Product_Variation();
		$low->set_parent_id( $variable->get_id() );
		$low->set_attributes( array( 'size' => 'small' ) );
		$low->set_regular_price( (string) $low_price );
		$low->set_status( 'publish' );
		$low->save();

		$high = new WC_Product_Variation();
		$high->set_parent_id( $variable->get_id() );
		$high->set_attributes( array( 'size' => 'large' ) );
		$high->set_regular_price( (string) $high_price );
		$high->set_status( 'publish' );
		$high->save();

		WC_Product_Variable::sync( $variable->get_id() );

		return wc_get_product( $variable->get_id() );
	}

	/**
	 * Ensure tax display is set to 'excl' so the price HTML reflects raw
	 * regular prices without any base location tax adjustments.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_tax_display_shop', 'excl' );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * @testdox Grouped product price HTML should span the lowest min and highest max of variable children.
	 */
	public function test_get_price_html_uses_full_range_of_variable_children() {
		$var1 = $this->create_variable_product_with_prices( 10, 50 );
		$var2 = $this->create_variable_product_with_prices( 20, 100 );

		$grouped = new WC_Product_Grouped();
		$grouped->set_name( 'Grouped with Variables' );
		$grouped->set_status( 'publish' );
		$grouped->set_children( array( $var1->get_id(), $var2->get_id() ) );
		$grouped->save();

		$grouped     = wc_get_product( $grouped->get_id() );
		$price_html  = $grouped->get_price_html();
		$plain_price = wp_strip_all_tags( html_entity_decode( $price_html ) );

		$this->assertStringContainsString( '10', $plain_price, 'Grouped price HTML should include the lowest variation price (10).' );
		$this->assertStringContainsString( '100', $plain_price, 'Grouped price HTML should include the highest variation price (100).' );
		$this->assertStringNotContainsString( '50.00 &ndash; 50.00', $price_html, 'Grouped price HTML must not collapse the range to the highest of the minimums.' );
	}

	/**
	 * @testdox Grouped get_max_price() should return the highest variation price across all variable children.
	 */
	public function test_get_max_price_returns_highest_variation_price() {
		$var1 = $this->create_variable_product_with_prices( 10, 50 );
		$var2 = $this->create_variable_product_with_prices( 20, 100 );

		$grouped = new WC_Product_Grouped();
		$grouped->set_children( array( $var1->get_id(), $var2->get_id() ) );
		$grouped->save();

		$grouped = wc_get_product( $grouped->get_id() );

		$this->assertSame( wc_format_decimal( 10 ), $grouped->get_min_price(), 'Min price should be the smallest variation price.' );
		$this->assertSame( wc_format_decimal( 100 ), $grouped->get_max_price(), 'Max price should be the largest variation price.' );
	}

	/**
	 * @testdox Grouped get_min/max_price() should remain correct for simple children.
	 */
	public function test_min_and_max_price_for_simple_children() {
		$simple_low = new WC_Product_Simple();
		$simple_low->set_name( 'Simple low' );
		$simple_low->set_regular_price( '5' );
		$simple_low->set_price( '5' );
		$simple_low->set_status( 'publish' );
		$simple_low->save();

		$simple_high = new WC_Product_Simple();
		$simple_high->set_name( 'Simple high' );
		$simple_high->set_regular_price( '25' );
		$simple_high->set_price( '25' );
		$simple_high->set_status( 'publish' );
		$simple_high->save();

		$grouped = new WC_Product_Grouped();
		$grouped->set_children( array( $simple_low->get_id(), $simple_high->get_id() ) );
		$grouped->save();

		$grouped = wc_get_product( $grouped->get_id() );

		$this->assertSame( wc_format_decimal( 5 ), $grouped->get_min_price(), 'Min price should match the cheapest simple child.' );
		$this->assertSame( wc_format_decimal( 25 ), $grouped->get_max_price(), 'Max price should match the most expensive simple child.' );

		$price_html  = $grouped->get_price_html();
		$plain_price = wp_strip_all_tags( html_entity_decode( $price_html ) );
		$this->assertStringContainsString( '5', $plain_price, 'Simple children price range should include the cheapest price.' );
		$this->assertStringContainsString( '25', $plain_price, 'Simple children price range should include the most expensive price.' );
	}

	/**
	 * @testdox Grouped product mixing simple and variable children should display the combined min/max range.
	 */
	public function test_mixed_simple_and_variable_children_range() {
		$simple = new WC_Product_Simple();
		$simple->set_name( 'Simple mid' );
		$simple->set_regular_price( '30' );
		$simple->set_price( '30' );
		$simple->set_status( 'publish' );
		$simple->save();

		$variable = $this->create_variable_product_with_prices( 5, 200 );

		$grouped = new WC_Product_Grouped();
		$grouped->set_children( array( $simple->get_id(), $variable->get_id() ) );
		$grouped->save();

		$grouped = wc_get_product( $grouped->get_id() );

		$this->assertSame( wc_format_decimal( 5 ), $grouped->get_min_price(), 'Min should be lowest across all children (variable variation $5).' );
		$this->assertSame( wc_format_decimal( 200 ), $grouped->get_max_price(), 'Max should be highest across all children (variable variation $200).' );
	}
}
