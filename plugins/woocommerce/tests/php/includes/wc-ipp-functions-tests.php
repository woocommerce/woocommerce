<?php

/**
 * Class WC_Mobile_Messaging_Handler_Test file.
 *
 * @package WooCommerce\Tests
 */
class WC_Ipp_Functions_Test extends WC_Unit_Test_Case {

	/**
	 * Tests that order is eligible for IPP if it meets all required conditions
	 */
	public function test_returns_true_if_order_is_ipp_eligible() {
		$order = WC_Mobile_Messaging_Handler_Test::generate_ipp_eligible_order();

		$result = is_order_in_person_payment_eligible( $order );

		$this->assertTrue( $result );
	}

	/**
	 * Tests that order is not eligible for IPP when it has one of not supported core statuses
	 */
	public function test_returns_false_if_order_has_NOT_required_status() {
		$invalid_statuses = array( 'completed', 'cancelled', 'refunded', 'failed', 'trash' );

		foreach ( $invalid_statuses as $invalid_status ) {
			$order = WC_Mobile_Messaging_Handler_Test::generate_ipp_eligible_order();
			$order->set_status( $invalid_status );

			$result = is_order_in_person_payment_eligible( $order );

			$this->assertFalse( $result );
		}
	}

	/**
	 * Tests that order is eligible for IPP when it has one of supported core statuses
	 */
	public function test_returns_true_if_order_has_required_status() {
		$valid_statuses = array( 'pending', 'on-hold', 'processing' );

		foreach ( $valid_statuses as $valid_status ) {
			$order = WC_Mobile_Messaging_Handler_Test::generate_ipp_eligible_order();
			$order->set_status( $valid_status );

			$result = is_order_in_person_payment_eligible( $order );

			$this->assertTrue( $result );
		}
	}

	/**
	 * Tests that order is not eligible for IPP when it has one of not supported payment methods
	 */
	public function test_returns_false_if_order_has_NOT_required_payment_method() {
		$invalid_methods = array( 'bacs', 'cheque', 'paypal' );

		foreach ( $invalid_methods as $invalid_status ) {
			$order = WC_Mobile_Messaging_Handler_Test::generate_ipp_eligible_order();
			$order->set_payment_method( $invalid_status );

			$result = is_order_in_person_payment_eligible( $order );

			$this->assertFalse( $result );
		}
	}

	/**
	 * Tests that order is eligible for IPP when it has one of supported payment methods
	 */
	public function test_returns_true_if_order_has_required_payment_method() {
		$valid_method = array( 'cod', 'woocommerce_payments', 'none' );

		foreach ( $valid_method as $valid_status ) {
			$order = WC_Mobile_Messaging_Handler_Test::generate_ipp_eligible_order();
			$order->set_payment_method( $valid_status );

			$result = is_order_in_person_payment_eligible( $order );

			$this->assertTrue( $result );
		}
	}

	/**
	 * Tests that order is not eligible for IPP when it's paid
	 */
	public function test_returns_false_if_order_is_paid() {
		$order = WC_Mobile_Messaging_Handler_Test::generate_ipp_eligible_order();

		$order->set_date_paid( '2022-08-05T00:00:00+00:00' );
		$result = is_order_in_person_payment_eligible( $order );

		$this->assertFalse( $result );
	}
}
