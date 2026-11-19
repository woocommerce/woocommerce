<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RegulatoryFields;

use WC_Helper_Order;
use WC_Privacy_Erasers;
use WC_Privacy_Exporters;
use WC_Unit_Test_Case;

/**
 * Tests for the Privacy class, through the core personal data exporter and eraser it hooks into.
 */
class PrivacyTest extends WC_Unit_Test_Case {

	private const EMAIL = 'regulatory-privacy@example.com';

	/**
	 * @testdox Should include the CPF/CNPJ, formatted, in the customer's exported order data.
	 */
	public function test_export_includes_value(): void {
		$this->create_order_with_tax_id();

		$response = WC_Privacy_Exporters::order_data_exporter( self::EMAIL, 1 );

		$exported = array_column( $response['data'][0]['data'], 'value', 'name' );
		$this->assertArrayHasKey( 'CPF/CNPJ', $exported );
		$this->assertSame( '529.982.247-25', $exported['CPF/CNPJ'] );
	}

	/**
	 * @testdox Should delete the CPF/CNPJ when the order's personal data is erased.
	 */
	public function test_erase_deletes_value(): void {
		$order = $this->create_order_with_tax_id();

		WC_Privacy_Erasers::remove_order_personal_data( $order );

		$this->assertFalse( wc_get_order( $order->get_id() )->meta_exists( '_wc_regulatory_br_tax_id' ) );
	}

	/**
	 * Create a saved order with a CPF.
	 *
	 * @return \WC_Order
	 */
	private function create_order_with_tax_id(): \WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_billing_email( self::EMAIL );
		$order->update_meta_data( '_wc_regulatory_br_tax_id', '52998224725' );
		$order->save();

		return $order;
	}
}
