<?php

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Tests\Internal\Fulfillments\Helpers\FulfillmentsHelper;

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
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
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
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_email_footer' );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertFalse( empty( $content ) );
	}

	/**
	 * Test that order meta function outputs linked meta.
	 */
	public function test_order_meta() {
		add_filter(
			'woocommerce_email_order_meta_keys',
			function () {
				return array( 'dummy_key' );
			}
		);
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->add_meta_data( 'dummy_key', 'dummy_meta_value' );
		$order->save();

		$email_object = new WC_Emails();
		ob_start();
		$email_object->order_meta( $order, true, true );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertStringContainsString( 'dummy_key', $content );
		$this->assertStringContainsString( 'dummy_meta_value', $content );
	}

	/**
	 * Test that fulfillment meta function outputs linked meta.
	 */
	public function test_fulfillment_meta() {
		// Ensure the FulfillmentsController is registered, which is necessary for the translation of meta keys.
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$container  = wc_get_container();
		$controller = $container->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();

		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id'   => $order->get_id(),
				'entity_type' => 'WC_Order',
			)
		);

		add_filter(
			'woocommerce_fulfillment_meta_key_translations',
			function ( $translations ) {
				$translations['test_meta_key'] = __( 'Test meta key', 'woocommerce' );
				return $translations;
			}
		);

		$email_object = new WC_Emails();
		ob_start();
		$email_object->fulfillment_meta( $order, $fulfillment, true, true );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertStringContainsString( 'Test meta key', $content );
		$this->assertStringContainsString( 'test_meta_value', $content );
	}
}
