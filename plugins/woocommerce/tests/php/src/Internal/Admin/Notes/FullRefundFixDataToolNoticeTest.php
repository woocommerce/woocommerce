<?php
/**
 * Tests for FullRefundFixDataToolNotice class.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Notes;

use Automattic\WooCommerce\Internal\Admin\Notes\FullRefundFixDataToolNotice;
use Automattic\WooCommerce\Admin\Notes\Note;
use WC_Unit_Test_Case;

/**
 * Class FullRefundFixDataToolNoticeTest
 */
class FullRefundFixDataToolNoticeTest extends WC_Unit_Test_Case {

	/**
	 * Test is_applicable returns false when the analytics feature is disabled.
	 */
	public function test_is_applicable_returns_false_when_analytics_disabled() {
		update_option( 'woocommerce_analytics_enabled', 'no' );
		delete_option( 'woocommerce_db_version' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );

		$this->assertFalse( FullRefundFixDataToolNotice::is_applicable() );
	}

	/**
	 * Test is_applicable returns false for stores without legacy refund data.
	 */
	public function test_is_applicable_returns_false_without_legacy_refund_data() {
		update_option( 'woocommerce_db_version', '10.2.0' );
		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		delete_option( 'woocommerce_analytics_show_old_refund_data_tool' );

		$this->assertFalse( FullRefundFixDataToolNotice::is_applicable() );
	}

	/**
	 * Test is_applicable returns true when the store has legacy refund data
	 * and the DB schema is below the threshold where new data applies.
	 */
	public function test_is_applicable_returns_true_with_legacy_refund_data() {
		delete_option( 'woocommerce_db_version' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );

		$this->assertTrue( FullRefundFixDataToolNotice::is_applicable() );
	}

	/**
	 * Test is_applicable returns false once the underlying data has been fixed
	 * (legacy flag cleared) even if the tool row is still flagged as visible
	 * pending merchant dismissal. The notice follows the data, not the tool row.
	 */
	public function test_is_applicable_returns_false_once_data_fixed_even_if_tool_still_visible() {
		update_option( 'woocommerce_db_version', '10.2.0' );
		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		update_option( 'woocommerce_analytics_show_old_refund_data_tool', 'yes' );

		$this->assertFalse( FullRefundFixDataToolNotice::is_applicable() );
	}

	/**
	 * Test get_note returns note with expected content and action when applicable.
	 */
	public function test_get_note_returns_note_when_applicable() {
		delete_option( 'woocommerce_db_version' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );

		$note = FullRefundFixDataToolNotice::get_note();

		$this->assertInstanceOf( Note::class, $note );
		$this->assertEquals( 'Fix your refund data in Analytics', $note->get_title() );
		$this->assertEquals( Note::E_WC_ADMIN_NOTE_WARNING, $note->get_type() );

		$actions = $note->get_actions();
		$this->assertCount( 1, $actions, 'Note should have 1 action' );
		$this->assertEquals( 'full-refund-fix-data-tool_view', $actions[0]->name );
		$this->assertStringContainsString( 'page=wc-status', $actions[0]->query );
		$this->assertStringContainsString( 'tab=tools', $actions[0]->query );
	}

	/**
	 * Test get_note returns null when not applicable.
	 */
	public function test_get_note_returns_null_when_not_applicable() {
		update_option( 'woocommerce_db_version', '10.2.0' );
		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		delete_option( 'woocommerce_analytics_show_old_refund_data_tool' );

		$this->assertNull( FullRefundFixDataToolNotice::get_note() );
	}

	/**
	 * Test that the note is added via possibly_add_note for stores with legacy refund data.
	 */
	public function test_possibly_add_note_adds_note_when_applicable() {
		delete_option( 'woocommerce_db_version' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );

		FullRefundFixDataToolNotice::possibly_add_note();

		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( FullRefundFixDataToolNotice::NOTE_NAME );

		$this->assertNotEmpty( $note_ids, 'Note should be created when the store has legacy refund data' );
	}

	/**
	 * Test that possibly_add_note prevents duplicates.
	 */
	public function test_possibly_add_note_prevents_duplicates() {
		delete_option( 'woocommerce_db_version' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );

		FullRefundFixDataToolNotice::possibly_add_note();
		FullRefundFixDataToolNotice::possibly_add_note();

		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( FullRefundFixDataToolNotice::NOTE_NAME );

		$this->assertCount( 1, $note_ids, 'Only one note should exist' );
	}

	/**
	 * Test that the note is removed once the store no longer has legacy refund data.
	 */
	public function test_delete_if_not_applicable_removes_note_once_fixed() {
		delete_option( 'woocommerce_db_version' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );
		FullRefundFixDataToolNotice::possibly_add_note();

		update_option( 'woocommerce_db_version', '10.2.0' );
		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		delete_option( 'woocommerce_analytics_show_old_refund_data_tool' );
		FullRefundFixDataToolNotice::delete_if_not_applicable();

		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( FullRefundFixDataToolNotice::NOTE_NAME );

		$this->assertEmpty( $note_ids, 'Note should be removed once the store no longer has legacy refund data' );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( FullRefundFixDataToolNotice::NOTE_NAME );

		foreach ( $note_ids as $note_id ) {
			$note = \Automattic\WooCommerce\Admin\Notes\Notes::get_note( $note_id );
			if ( $note ) {
				$note->delete();
			}
		}

		delete_option( 'woocommerce_analytics_enabled' );
		delete_option( 'woocommerce_db_version' );
		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		delete_option( 'woocommerce_analytics_show_old_refund_data_tool' );
	}
}
