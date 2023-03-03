<?php
/**
 * Spec runner tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_SpecRunner
 */
class WC_Admin_Tests_RemoteInboxNotifications_SpecRunner extends WC_Unit_Test_Case {

	/**
	 * Build up a spec given the supplied parameters.
	 *
	 * @param string $url url for the action.
	 * @param bool   $url_is_admin_query is_admin_query boolean for the action.
	 *
	 * @return object The spec object.
	 */
	private function get_spec( $url, $url_is_admin_query ) {
		return json_decode(
			'{
				"slug": "test",
				"status": "unactioned",
				"type": "info",
				"locales": [{
					"locale": "en_US",
					"title": "Title",
					"content": "Content"
				}],
				"rules": [],
				"actions": [ {
					"name": "test-action",
	 				"locales": [
	 				{
						"locale": "en_US",
						"label": "Action label"
					}],
					"url": "' . $url . '",
					"url_is_admin_query": ' . ( $url_is_admin_query ? 'true' : 'false' ) . ',
					"status": "unactioned"
				}]
			}'
		);
	}

	/**
	 * Tests get_url function with wp-admin url
	 *
	 * @group fast
	 */
	public function test_get_url_with_wp_admin_url() {
		$spec = $this->get_spec( 'plugins.php', true );
		SpecRunner::run_spec( $spec, RemoteInboxNotificationsEngine::get_stored_state() );
		$note   = Notes::get_note_by_name( $spec->slug );
		$action = $note->get_action( 'test-action' );
		$this->assertEquals( admin_url( 'plugins.php' ), $action->query );
	}

	/**
	 * Tests get url function with WooCommerce Admin url.
	 *
	 * @group fast
	 */
	public function test_get_url_with_wc_admin_url() {
		$spec = $this->get_spec( '&path=wc-addons', true );
		SpecRunner::run_spec( $spec, RemoteInboxNotificationsEngine::get_stored_state() );
		$note   = Notes::get_note_by_name( $spec->slug );
		$action = $note->get_action( 'test-action' );
		$this->assertEquals( wc_admin_url( '&path=wc-addons' ), $action->query );
	}

	/**
	 * Tests get url with external url.
	 *
	 * @group fast
	 */
	public function test_get_url_with_external_url() {
		$spec = $this->get_spec( 'http://test.com', false );
		SpecRunner::run_spec( $spec, RemoteInboxNotificationsEngine::get_stored_state() );
		$note   = Notes::get_note_by_name( $spec->slug );
		$action = $note->get_action( 'test-action' );
		$this->assertEquals( 'http://test.com', $action->query );
	}

	/**
	 * Tests get actions function.
	 *
	 * @group fast
	 */
	public function test_get_actions() {
		$spec    = $this->get_spec( 'http://test.com', false );
		$actions = SpecRunner::get_actions( $spec );
		$this->assertCount( 1, $actions );
		$this->assertEquals( $actions[0]->name, 'test-action' );
	}

	/**
	 * Tests get actions function with no actions in specs.
	 *
	 * @group fast
	 */
	public function test_get_actions_with_empty_specs() {
		$actions = SpecRunner::get_actions( array() );
		$this->assertCount( 0, $actions );

		$actions = SpecRunner::get_actions(
			array(
				'actions' => array(),
			)
		);
		$this->assertCount( 0, $actions );
	}
}
