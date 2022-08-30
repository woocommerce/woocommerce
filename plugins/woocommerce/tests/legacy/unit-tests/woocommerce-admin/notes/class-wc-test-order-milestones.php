<?php
/**
 * OrderMilestones note tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use \Automattic\WooCommerce\Internal\Admin\Notes\OrderMilestones;
use Automattic\WooCommerce\Admin\Notes\Notes;

/**
 * Class WC_Admin_Tests_Order_Milestones
 */
class WC_Admin_Tests_Order_Milestones extends WC_Unit_Test_Case {

	/**
	 * @var OrderMilestones
	 */
	private $instance;

	/**
	 * setUp
	 */
	public function setUp(): void {
		parent::setUp();
		$this->instance = new OrderMilestones();
	}

	/**
	 * Tests can_be_added method return false when woocommerce_admin_order_milestones_enabled is false.
	 */
	public function test_can_be_added_when_milestones_disabled() {
		add_filter(
			'woocommerce_admin_order_milestones_enabled',
			function ( $enabled ) {
				return false;
			}
		);
		$this->assertFalse( $this->instance->can_be_added() );
	}

	/**
	 * Tests can_be_added method return false when no orders received.
	 */
	public function test_can_be_added_when_no_orders_received() {
		$this->assertFalse( $this->instance->can_be_added() );
	}

	/**
	 * Tests can_be_added method return true when one orders received.
	 */
	public function test_can_be_added_when_orders_received() {
		WC_Helper_Order::create_order();
		$this->assertTrue( $this->instance->can_be_added() );
	}

	/**
	 * Tests get_note_by_milestone method when milestone is 1.
	 */
	public function test_get_note_by_milestone_when_milestone_is_1() {
		$note = $this->instance->get_note_by_milestone( 1 );
		$this->assertEquals( $note->get_title(), 'First order received' );
		$this->assertEquals( $note->get_content(), 'Congratulations on getting your first order! Now is a great time to learn how to manage your orders.' );
		$this->assertEquals( $note->get_actions()[0]->label, 'Learn more' );
	}

	/**
	 * Tests get_note_by_milestone method when no milestone is 10.
	 */
	public function test_get_note_by_milestone_when_milestone_is_10() {
		$note = $this->instance->get_note_by_milestone( 10 );
		$this->assertEquals( $note->get_title(), 'Congratulations on processing 10 orders!' );
		$this->assertEquals( $note->get_content(), "You've hit the 10 orders milestone! Look at you go. Browse some WooCommerce success stories for inspiration." );
		$this->assertEquals( $note->get_actions()[0]->label, 'Browse' );
	}

	/**
	 * Tests get_note_by_milestone method when no milestone is 100.
	 */
	public function test_get_note_by_milestone_when_milestone_is_100() {
		$note = $this->instance->get_note_by_milestone( 100 );
		$this->assertEquals( $note->get_title(), 'Congratulations on processing 100 orders!' );
		$this->assertEquals( $note->get_content(), 'Another order milestone! Take a look at your Orders Report to review your orders to date.' );
		$this->assertEquals( $note->get_actions()[0]->label, 'Review your orders' );
	}

	/**
	 * Tests possibly_add_note method when no orders received.
	 */
	public function test_possibly_add_note_when_no_orders_received() {
		$this->instance->possibly_add_note();
		$this->assertFalse( Notes::get_note_by_name( OrderMilestones::NOTE_NAME ) );
	}

	/**
	 * Tests possibly_add_note method when no orders received.
	 */
	public function test_possibly_add_note_when_first_order_received() {
		WC_Helper_Order::create_order();
		$this->instance->possibly_add_note();
		$note = Notes::get_note_by_name( OrderMilestones::NOTE_NAME );
		$this->assertEquals( $note->get_title(), 'First order received' );
	}

	/**
	 * Tests possibly_add_note method when tenth order received.
	 */
	public function test_possibly_add_note_when_tenth_order_received() {
		for ( $i = 0; $i < 10; $i++ ) {
			WC_Helper_Order::create_order();
		}
		$this->instance->possibly_add_note();
		$note = Notes::get_note_by_name( OrderMilestones::NOTE_NAME );
		$this->assertEquals( $note->get_content(), "You've hit the 10 orders milestone! Look at you go. Browse some WooCommerce success stories for inspiration." );
	}


	/**
	 * Tests get_note method return false when note does not exist in db.
	 */
	public function test_get_note_when_note_not_exist_in_db() {
		$this->assertFalse( OrderMilestones::get_note() );
	}

	/**
	 * Tests get_note method return note when note exists in db.
	 */
	public function test_get_note_when_note_exists_in_db() {
		WC_Helper_Order::create_order();
		$this->instance->possibly_add_note();
		$note = OrderMilestones::get_note();
		$this->assertEquals( $note->get_title(), 'First order received' );
	}
}
