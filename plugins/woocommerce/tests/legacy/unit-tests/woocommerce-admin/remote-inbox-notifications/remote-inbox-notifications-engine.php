<?php
/**
 * RemoteInboxNotificationsEngine tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\DataSourcePoller;
use Automattic\WooCommerce\Admin\Notes\Note;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_SpecRunner
 */
class WC_Admin_Tests_RemoteInboxNotifications_RemoteInboxNotificationsEngine extends WC_Unit_Test_Case {

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter(
			'transient_woocommerce_admin_' . DataSourcePoller::ID . '_specs',
			function( $value ) {
				if ( $value ) {
					return $value;
				}
				$specs = array(
					'zh_TW' => json_decode(
						'[{
						"slug": "test",
						"status": "unactioned",
						"type": "info",
						"locales": [{
							"locale": "zh_TW",
							"title": "名稱",
							"content": "內容"
						}],
						"rules": [],
						"actions": [ {
							"name": "test-action",
							 "locales": [
							 {
								"locale": "zh_TW",
								"label": "標籤"
							}],
							"url": "test",
							"url_is_admin_query": false,
							"status": "unactioned"
						}]
					}]'
					),
				);

				return $specs;
			}
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_transient( 'woocommerce_admin_' . DataSourcePoller::ID . '_specs' );
		remove_all_filters( 'transient_woocommerce_admin_' . DataSourcePoller::ID . '_specs' );
	}


	/**
	 * Tests get_note_from_db function with a invalid note.
	 *
	 */
	public function test_get_note_from_db_with_invalid_note() {
		$invalid_note = array(
			'note_name' => 'invalid',
		);
		$this->assertEquals( RemoteInboxNotificationsEngine::get_note_from_db( $invalid_note ), $invalid_note );
	}

	/**
	 * Tests get_note_from_db function when locale is the same
	 *
	 */
	public function test_get_note_from_db_when_the_locale_is_the_same() {
		$note = new Note();
		$note->set_locale( get_user_locale() );
		$this->assertEquals( RemoteInboxNotificationsEngine::get_note_from_db( $note ), $note );
	}

	/**
	 * Tests get_note_from_db function when locales are different
	 *
	 */
	public function test_get_note_from_db_when_locales_are_different() {
		$note_from_db = new Note();
		$note_from_db->set_locale( 'en_US' );
		$note_from_db->set_name( 'test' );
		$note_from_db->set_actions(
			array(
				(object) array(
					'id'   => 123,
					'name' => 'test-action',
				),
			)
		);
		add_filter(
			'locale',
			function( $locale ) {
				return 'zh_TW';
			}
		);

		$note = RemoteInboxNotificationsEngine::get_note_from_db( $note_from_db );
		$this->assertEquals( $note->get_title(), '名稱' );
		$this->assertEquals( $note->get_content(), '內容' );
		$this->assertEquals( $note->get_actions()[0]->label, '標籤' );
		$this->assertEquals( $note->get_actions()[0]->id, 123 );

	}
}
