<?php

use Automattic\WooCommerce\Admin\Notes\Note;

/**
 * DB Update test for wc_admin_update_300_update_is_read_from_last_read()
 *
 * @package WooCommerce\Admin\Tests\DBUpdates
 */
class WC_Admin_Tests_Update_Is_Read_From_Last_Read extends WC_Unit_Test_Case {
	/**
	 * @var object current user
	 */
	private $user;

	/**
	 * setUp
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Given woocommerce_admin_activity_panel_inbox_last_read does not exist
	 * When the update runs
	 * Then it should not update is_read col
	 */
	public function test_update_does_not_run_when_usermeta_does_not_exist() {
		global $wpdb;

		$wpdb->query(
			"
			delete from {$wpdb->prefix}usermeta where meta_key = 'woocommerce_admin_activity_panel_inbox_last_read'
		"
		);

		wc_admin_update_300_update_is_read_from_last_read();

		$notes_with_is_read = $wpdb->get_var(
			"select count(*) from {$wpdb->prefix}wc_admin_notes where is_read = 1
		"
		);

		$this->assertTrue( '0' === $notes_with_is_read );
	}

	/**
	 * Give woocommerce_admin_activity_panel_inbox_last_read
	 * When the update runs
	 * Then it should update notes where date_created value is less than woocommerce_admin_activity_panel_inbox_last_read
	 */
	public function test_it_updates_is_read_when_date_created_value_is_less_than_last_read() {
		global $wpdb;
		$time = time();

		$meta_key = 'woocommerce_admin_activity_panel_inbox_last_read';

		wp_set_current_user( $this->user );
		$wpdb->query( "delete from {$wpdb->prefix}wc_admin_notes" );

		// Note with date_created less than woocommerce_admin_activity_panel_inbox_last_read.
		$note = new Note();
		$note->set_title( 'test1' );
		$note->set_content( 'test1' );
		$note->set_name( 'test1' );
		$note->save();
		$date_created_1 = gmdate( 'Y-m-d H:i:s', $time - 3600 );

		// Note with date_created greater than woocommerce_admin_activity_panel_inbox_last_read.
		$note = new Note();
		$note->set_title( 'test2' );
		$note->set_content( 'test2' );
		$note->set_name( 'test2' );
		$note->save();
		$date_created_2 = gmdate( 'Y-m-d H:i:s', $time + 3600 );

		// phpcs:ignore
		$wpdb->query( "update {$wpdb->prefix}wc_admin_notes set date_created = '{$date_created_1}' where name='test1'" );
		// phpcs:ignore
		$wpdb->query( "update {$wpdb->prefix}wc_admin_notes set date_created = '{$date_created_2}' where name='test2'" );

		update_user_meta( $this->user, $meta_key, $time * 1000 );

		wc_admin_update_300_update_is_read_from_last_read();

		$notes_with_is_read = $wpdb->get_var(
			"select count(*) from {$wpdb->prefix}wc_admin_notes where is_read = 1
		"
		);

		$this->assertTrue( '1' === $notes_with_is_read );

		// phpcs:ignore
		$last_read_count = $wpdb->get_var("select count(*) from {$wpdb->usermeta} where meta_key='{$meta_key}'");
		$this->assertTrue( '0' === $last_read_count );
	}
}
