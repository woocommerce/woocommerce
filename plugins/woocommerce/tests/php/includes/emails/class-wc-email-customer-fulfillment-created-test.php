<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;

/**
 * WC_Email_Customer_Fulfillment_Created test.
 *
 * @covers WC_Email_Customer_Fulfillment_Created
 */
class WC_Email_Customer_Fulfillment_Created_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Email_Customer_Fulfillment_Created
	 */
	private $sut;

	/**
	 * Load up the email classes since they aren't loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-fulfillment-created.php';
	}

	/**
	 * @testdox Default subject uses singular form when fulfillment has one item.
	 */
	public function test_default_subject_singular_for_one_item(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$fulfillment = new Fulfillment();
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 1,
				),
			)
		);

		$this->sut = new WC_Email_Customer_Fulfillment_Created();
		$this->sut->trigger( $order->get_id(), $fulfillment, $order );

		$subject = $this->sut->get_default_subject();

		$this->assertStringContainsString( 'An item', $subject, 'Subject should use singular form for single item fulfillment' );
	}

	/**
	 * @testdox Default subject uses plural form when fulfillment has multiple items.
	 */
	public function test_default_subject_plural_for_multiple_items(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$fulfillment = new Fulfillment();
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 1,
				),
				array(
					'item_id' => 2,
					'qty'     => 1,
				),
			)
		);

		$this->sut = new WC_Email_Customer_Fulfillment_Created();
		$this->sut->trigger( $order->get_id(), $fulfillment, $order );

		$subject = $this->sut->get_default_subject();

		$this->assertStringContainsString( 'Items', $subject, 'Subject should use plural form for multi-item fulfillment' );
		$this->assertStringNotContainsString( 'An item', $subject, 'Subject should not contain singular form for multi-item fulfillment' );
	}

	/**
	 * @testdox Default heading uses singular form when fulfillment has one item.
	 */
	public function test_default_heading_singular_for_one_item(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$fulfillment = new Fulfillment();
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 1,
				),
			)
		);

		$this->sut = new WC_Email_Customer_Fulfillment_Created();
		$this->sut->trigger( $order->get_id(), $fulfillment, $order );

		$heading = $this->sut->get_default_heading();

		$this->assertStringContainsString( 'Your item is on the way!', $heading, 'Heading should use singular form for single item fulfillment' );
	}

	/**
	 * @testdox Default heading uses plural form when fulfillment has multiple items.
	 */
	public function test_default_heading_plural_for_multiple_items(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$fulfillment = new Fulfillment();
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 1,
				),
				array(
					'item_id' => 2,
					'qty'     => 1,
				),
			)
		);

		$this->sut = new WC_Email_Customer_Fulfillment_Created();
		$this->sut->trigger( $order->get_id(), $fulfillment, $order );

		$heading = $this->sut->get_default_heading();

		$this->assertStringContainsString( 'Your items are on the way!', $heading, 'Heading should use plural form for multi-item fulfillment' );
	}

	/**
	 * @testdox Default subject uses plural form when one item has multiple quantity.
	 */
	public function test_default_subject_plural_for_single_item_multiple_qty(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$fulfillment = new Fulfillment();
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 3,
				),
			)
		);

		$this->sut = new WC_Email_Customer_Fulfillment_Created();
		$this->sut->trigger( $order->get_id(), $fulfillment, $order );

		$subject = $this->sut->get_default_subject();

		$this->assertStringContainsString( 'Items', $subject, 'Subject should use plural form when single item has qty > 1' );
		$this->assertStringNotContainsString( 'An item', $subject, 'Subject should not contain singular form when single item has qty > 1' );
	}

	/**
	 * @testdox Default heading uses plural form when one item has multiple quantity.
	 */
	public function test_default_heading_plural_for_single_item_multiple_qty(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$fulfillment = new Fulfillment();
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
			)
		);

		$this->sut = new WC_Email_Customer_Fulfillment_Created();
		$this->sut->trigger( $order->get_id(), $fulfillment, $order );

		$heading = $this->sut->get_default_heading();

		$this->assertStringContainsString( 'Your items are on the way!', $heading, 'Heading should use plural form when single item has qty > 1' );
	}

	/**
	 * @testdox Default subject uses singular form when no fulfillment is set.
	 */
	public function test_default_subject_singular_when_no_fulfillment(): void {
		$this->sut = new WC_Email_Customer_Fulfillment_Created();

		$subject = $this->sut->get_default_subject();

		$this->assertStringContainsString( 'An item', $subject, 'Subject should default to singular form when no fulfillment is set' );
	}

	/**
	 * @testdox Default heading uses singular form when no fulfillment is set.
	 */
	public function test_default_heading_singular_when_no_fulfillment(): void {
		$this->sut = new WC_Email_Customer_Fulfillment_Created();

		$heading = $this->sut->get_default_heading();

		$this->assertStringContainsString( 'Your item is on the way!', $heading, 'Heading should default to singular form when no fulfillment is set' );
	}
}
