<?php

/**
 * Class WC_Emails_Tests.
 */
class WC_Emails_Tests extends \WC_Unit_Test_Case {

	/**
	 * Test that email_header hooks are compatible with do_action calls with only param.
	 * This test should be dropped after all extensions are using compatible do_action calls.
	 */
	public function test_email_header_is_compatible_with_legacy_do_action() {
		$email_object = new WC_Emails();
		// 10 is expected priority of the hook.
		$this->assertEquals( 10, has_action( 'woocommerce_email_header', array( $email_object, 'email_header' ) ) );
		ob_start();
		do_action( 'woocommerce_email_header', 'header' );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertFalse( empty( $content ) );
	}

	/**
	 * Test that email_footer hooks are compatible with do_action calls with only param.
	 * This test should be dropped after all extensions are using compatible do_action calls.
	 */
	public function test_email_footer_is_compatible_with_legacy_do_action() {
		$email_object = new WC_Emails();
		// 10 is expected priority of the hook.
		$this->assertEquals( 10, has_action( 'woocommerce_email_footer', array( $email_object, 'email_footer' ) ) );
		ob_start();
		do_action( 'woocommerce_email_footer' );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertFalse( empty( $content ) );
	}
}
