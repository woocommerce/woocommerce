<?php
/**
 * Email notes tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use \Automattic\WooCommerce\Internal\Admin\Notes\MerchantEmailNotifications;
use \Automattic\WooCommerce\Admin\Notes\Note;
use \Automattic\WooCommerce\Admin\Notes\Notes;
use \Automattic\WooCommerce\Internal\Admin\Notes\EmailNotification;

/**
 * Class WC_Admin_Tests_Email_Notes
 */
class WC_Admin_Tests_Email_Notes extends WC_Unit_Test_Case {

	/**
	 * Setup test admin notes data. Called before every test.
	 *
	 * @since 3.5.0
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		WC_Helper_Admin_Notes::reset_notes_dbs();
		WC_Helper_Admin_Notes::add_notes_for_tests();
		WC_Helper_Admin_Notes::add_email_notes_for_test();
	}

	/**
	 * Tests EmailNotification default values.
	 */
	public function test_default_values_create_notification_email() {
		$note = new Note();
		$note->set_title( 'PHPUNIT_TEST_NOTE_EMAIL_TITLE' );
		$note->set_content( 'PHPUNIT_TEST_NOTE_EMAIL_CONTENT' );
		$note->set_type( Note::E_WC_ADMIN_NOTE_EMAIL );
		$note->set_name( 'PHPUNIT_TEST_NOTE_EMAIL_NAME' );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->set_is_snoozable( false );
		$note->set_layout( 'plain' );
		$note->set_image( '' );
		$content_data = array(
			'role' => 'administrator',
		);
		$note->set_content_data( (object) $content_data );
		$note->add_action(
			'PHPUNIT_TEST_EMAIL_ACTION_SLUG',
			'PHPUNIT_TEST_EMAIL_ACTION_LABEL',
			'?s=PHPUNIT_TEST_EMAIL_ACTION_URL'
		);
		$note->set_is_deleted( false );
		$notification_email = new EmailNotification( $note );

		$this->assertEquals( $notification_email->id, 'merchant_notification' );
		$this->assertEquals( $notification_email->get_default_heading(), $note->get_title() );
		$this->assertEquals( $notification_email->get_template_filename(), 'html-merchant-notification.php' );
		$this->assertEquals( $notification_email->get_template_filename( 'html' ), 'html-merchant-notification.php' );
		$this->assertEquals( $notification_email->get_template_filename( 'plain' ), 'plain-merchant-notification.php' );
	}

	/**
	 * Tests EmailNotification is created correctly.
	 */
	public function test_create_notification_email() {
		$data_store   = WC_Data_Store::load( 'admin-note' );
		$note_data    = $data_store->get_notes(
			array(
				'type'   => array( Note::E_WC_ADMIN_NOTE_EMAIL ),
				'status' => array( 'unactioned' ),
			)
		);
		$note         = Notes::get_note( $note_data[0]->note_id );
		$content_data = array(
			'heading' => 'PHPUNIT_TEST_EMAIL_HEADING',
			'role'    => 'administrator',
		);
		$note->set_content_data( (object) $content_data );
		$note->save();
		$note->set_image( '' );
		$notification_email                          = new EmailNotification( $note );
		$notification_email->opened_tracking_url     = 'PHPUNIT_TEST_NOTE_EMAIL_TRACKING_URL';
		$notification_email->trigger_note_action_url = 'PHPUNIT_TEST_NOTE_EMAIL_TRIGGER_ACTION_URL';
		$content_html                                = $notification_email->get_content_html();
		$content_plain                               = $notification_email->get_content_plain();

		$this->assertEquals( $notification_email->get_default_heading(), $content_data['heading'] );
		$this->assertEquals( $notification_email->get_default_subject(), $note->get_title() );
		$this->assertEquals( $notification_email->get_note_content(), $note->get_content() );
		$this->assertEquals( $notification_email->get_note_content(), $note->get_content() );
		$this->assertTrue( strpos( $content_html, 'PHPUNIT_TEST_NOTE_5_ACTION_URL' ) >= 0 );
		$this->assertTrue( strpos( $content_html, 'PHPUNIT_TEST_NOTE_5_ACTION_LABEL' ) >= 0 );
		$this->assertTrue( strpos( $content_html, 'PHPUNIT_TEST_NOTE_5_CONTENT' ) >= 0 );
		$this->assertTrue( strpos( $content_html, 'PHPUNIT_TEST_NOTE_EMAIL_TRACKING_URL' ) >= 0 );
		$this->assertTrue( strpos( $content_html, 'PHPUNIT_TEST_NOTE_EMAIL_TRIGGER_ACTION_URL' ) >= 0 );
		$this->assertTrue( strpos( $content_plain, 'PHPUNIT_TEST_NOTE_5_ACTION_URL' ) >= 0 );
		$this->assertTrue( strpos( $content_plain, 'PHPUNIT_TEST_NOTE_5_ACTION_LABEL' ) >= 0 );
		$this->assertTrue( strpos( $content_plain, 'PHPUNIT_TEST_NOTE_5_CONTENT' ) >= 0 );
		$this->assertTrue( strpos( $content_plain, 'PHPUNIT_TEST_EMAIL_HEADING' ) >= 0 );
	}

	/**
	 * Tests EmailNotification validations.
	 */
	public function test_create_invalid_notification_email() {
		$data_store   = WC_Data_Store::load( 'admin-note' );
		$note_data    = $data_store->get_notes(
			array(
				'type'   => array( Note::E_WC_ADMIN_NOTE_EMAIL ),
				'status' => array( 'unactioned' ),
			)
		);
		$note         = Notes::get_note( $note_data[0]->note_id );
		$content_data = array(
			'role' => 'invalid_role',
		);
		$note->set_content_data( (object) $content_data );
		$notification_email = new EmailNotification( $note );

		$this->assertEmpty( MerchantEmailNotifications::get_notification_recipients( $note ) );
		$this->assertEmpty( $notification_email->get_template_filename( 'wrong_type' ) );
	}
}
