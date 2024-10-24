<?php


/**
 * WC_Order_Item_Tax_Test Class.
 */
class WC_Order_Item_Tax_Test extends WC_Unit_Test_Case {
	/**
	 * @var object WC_Order_Item_Tax;
	 */
	private $order_item_tax;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		$this->order_item_tax = new \WC_Order_Item_Tax();

		parent::setUp();
	}

	/**
	 * Test save label.
	 * @param string $label label to save.
	 * @param string $expected_label expected label.
	 * @testDox Test that the label is saved correctly
	 * @dataProvider data_provider_test_set_label
	 */
	public function test_set_label( $label, $expected_label ) {
		$this->order_item_tax->set_label( $label );

		$actual_label = $this->order_item_tax->get_label();

		$this->assertSame( $expected_label, $actual_label );
	}

	/**
	 * Data provider.
	 */
	public function data_provider_test_set_label(): array {
		return array(
			'empty'                => array( '', __( 'Tax', 'woocommerce' ) ),
			'simple string'        => array( 'Test Tax', 'Test Tax' ),
			'%text'                => array( 'Tax %15', 'Tax &#37;15' ),
			'% text'               => array( 'Tax % 15', 'Tax &#37; 15' ),
			'text%'                => array( 'Tax 15%', 'Tax 15&#37;' ),
			'text %'               => array( 'Tax 15 %', 'Tax 15 &#37;' ),
			'mix of special chars' => array( '<test> "Tax %15 %D0 \t', '"Tax &#37;15 &#37;D0 \t' ),
			'non-latin characters' => array( '%מַס %Φόρος', '&#37;מַס &#37;Φόρος' ),

		);
	}
}
