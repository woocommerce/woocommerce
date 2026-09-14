<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * WC_Email_Customer_Partially_Refunded_Order test.
 *
 * @covers WC_Email_Customer_Partially_Refunded_Order
 */
class WC_Email_Customer_Partially_Refunded_Order_Test extends \WC_Unit_Test_Case {
	/**
	 * Features controller instance.
	 *
	 * @var FeaturesController
	 */
	private $features_controller;

	/**
	 * Original block_email_editor feature state.
	 *
	 * @var bool
	 */
	private $original_block_email_editor_enabled;

	/**
	 * Load up the email classes since they aren't loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-refunded-order.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-partially-refunded-order.php';
		require_once $bootstrap->plugin_dir . '/includes/class-wc-emails.php';

		$this->features_controller                 = wc_get_container()->get( FeaturesController::class );
		$this->original_block_email_editor_enabled = $this->features_controller->feature_is_enabled( 'block_email_editor' );
	}

	/**
	 * Restore original feature state.
	 */
	public function tearDown(): void {
		$this->features_controller->change_feature_enable( 'block_email_editor', $this->original_block_email_editor_enabled );
		parent::tearDown();
	}

	/**
	 * @testdox Email has correct ID set to 'customer_partially_refunded_order'.
	 */
	public function test_email_has_correct_id() {
		// When instantiating the email class.
		$email = new WC_Email_Customer_Partially_Refunded_Order();

		// Then the email ID is correct.
		$this->assertEquals( 'customer_partially_refunded_order', $email->id );
	}

	/**
	 * @testdox Email has correct title set to 'Partially refunded order'.
	 */
	public function test_email_has_correct_title() {
		// When instantiating the email class.
		$email = new WC_Email_Customer_Partially_Refunded_Order();

		// Then the email title is correct.
		$this->assertEquals( 'Partially refunded order', $email->title );
	}

	/**
	 * @testdox Email has partial_refund property set to true.
	 */
	public function test_email_has_partial_refund_set_to_true() {
		// When instantiating the email class.
		$email = new WC_Email_Customer_Partially_Refunded_Order();

		// Then the partial_refund property is true.
		$this->assertTrue( $email->partial_refund );
	}

	/**
	 * @testdox Email has correct block template path set.
	 */
	public function test_email_has_correct_block_template_path() {
		// When instantiating the email class.
		$email = new WC_Email_Customer_Partially_Refunded_Order();

		// Then the block template path is correct.
		$this->assertEquals( 'emails/block/customer-partially-refunded-order.php', $email->template_block );
	}

	/**
	 * @testdox Email extends the WC_Email_Customer_Refunded_Order class.
	 */
	public function test_email_extends_refunded_order_email() {
		// When instantiating the email class.
		$email = new WC_Email_Customer_Partially_Refunded_Order();

		// Then the email is an instance of the parent class.
		$this->assertInstanceOf( WC_Email_Customer_Refunded_Order::class, $email );
	}

	/**
	 * @testdox Email is loaded by WC_Emails when block_email_editor feature is enabled.
	 */
	public function test_email_is_loaded_when_block_email_editor_is_enabled() {
		// Given the block_email_editor feature is enabled.
		$this->features_controller->change_feature_enable( 'block_email_editor', true );

		// When initializing emails.
		$emails = new WC_Emails();

		// Then the partially refunded order email is loaded.
		$this->assertArrayHasKey( 'WC_Email_Customer_Partially_Refunded_Order', $emails->emails );
		$this->assertInstanceOf( WC_Email_Customer_Partially_Refunded_Order::class, $emails->emails['WC_Email_Customer_Partially_Refunded_Order'] );
	}

	/**
	 * @testdox Email is not loaded by WC_Emails when block_email_editor feature is disabled.
	 */
	public function test_email_is_not_loaded_when_block_email_editor_is_disabled() {
		// Given the block_email_editor feature is disabled.
		$this->features_controller->change_feature_enable( 'block_email_editor', false );

		// When initializing emails.
		$emails = new WC_Emails();

		// Then the partially refunded order email is not loaded.
		$this->assertArrayNotHasKey( 'WC_Email_Customer_Partially_Refunded_Order', $emails->emails );
	}

	/**
	 * @testdox Email is marked as customer email.
	 */
	public function test_email_is_customer_email() {
		// When instantiating the email class.
		$email = new WC_Email_Customer_Partially_Refunded_Order();

		// Then the email is marked as a customer email.
		$this->assertTrue( $email->is_customer_email() );
	}

	/**
	 * @testdox Email inherits placeholders from parent class.
	 */
	public function test_email_inherits_placeholders_from_parent() {
		// When instantiating the email class.
		$email = new WC_Email_Customer_Partially_Refunded_Order();

		// Then the email has the expected placeholders.
		$this->assertArrayHasKey( '{order_date}', $email->placeholders );
		$this->assertArrayHasKey( '{order_number}', $email->placeholders );
	}

	/**
	 * @testdox Email uses partial refund subject from parent class.
	 */
	public function test_email_uses_partial_refund_subject() {
		// Given an order.
		$order = OrderHelper::create_order();
		$order->save();

		// When instantiating the email and setting the order.
		$email         = new WC_Email_Customer_Partially_Refunded_Order();
		$email->object = $order;

		// Populate placeholders.
		$email->placeholders['{order_number}'] = $order->get_order_number();

		// Then the subject contains partial refund text.
		$subject = $email->get_subject();
		$this->assertStringContainsString( 'partially refunded', strtolower( $subject ) );
	}

	/**
	 * @testdox Email uses partial refund heading from parent class.
	 */
	public function test_email_uses_partial_refund_heading() {
		// Given an order.
		$order = OrderHelper::create_order();
		$order->save();

		// When instantiating the email and setting the order.
		$email         = new WC_Email_Customer_Partially_Refunded_Order();
		$email->object = $order;

		// Populate placeholders.
		$email->placeholders['{order_number}'] = $order->get_order_number();

		// Then the heading contains partial refund text.
		$heading = $email->get_heading();
		$this->assertStringContainsString( 'Partial', $heading );
	}

	/**
	 * @testdox Partially refunded order email has different ID from regular refunded order email.
	 */
	public function test_partially_refunded_email_has_different_id_from_regular_email() {
		// When instantiating both email classes.
		$partial_email = new WC_Email_Customer_Partially_Refunded_Order();
		$regular_email = new WC_Email_Customer_Refunded_Order();

		// Then they have different IDs.
		$this->assertNotEquals( $regular_email->id, $partial_email->id );
		$this->assertEquals( 'customer_refunded_order', $regular_email->id );
		$this->assertEquals( 'customer_partially_refunded_order', $partial_email->id );
	}

	/**
	 * @testdox Partially refunded order email has different title from regular refunded order email.
	 */
	public function test_partially_refunded_email_has_different_title_from_regular_email() {
		// When instantiating both email classes.
		$partial_email = new WC_Email_Customer_Partially_Refunded_Order();
		$regular_email = new WC_Email_Customer_Refunded_Order();

		// Then they have different titles.
		$this->assertNotEquals( $regular_email->title, $partial_email->title );
	}

	/**
	 * @testdox Partially refunded order email has partial_refund set while regular email does not by default.
	 */
	public function test_partially_refunded_email_has_partial_refund_set_while_regular_does_not() {
		// When instantiating both email classes.
		$partial_email = new WC_Email_Customer_Partially_Refunded_Order();
		$regular_email = new WC_Email_Customer_Refunded_Order();

		// Then partially refunded email has partial_refund set to true.
		$this->assertTrue( $partial_email->partial_refund );

		// And regular email has partial_refund as null by default.
		$this->assertNull( $regular_email->partial_refund );
	}
}
