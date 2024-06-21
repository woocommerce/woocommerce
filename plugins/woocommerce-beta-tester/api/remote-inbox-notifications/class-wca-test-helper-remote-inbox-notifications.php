<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;

register_woocommerce_admin_test_helper_rest_route(
	'/remote-inbox-notifications',
	array( WCA_Test_Helper_Remote_Inbox_Notifications::class, 'get_items' ),
	array(
		'methods' => 'GET',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/remote-inbox-notifications/(?P<id>\d+)',
	array( WCA_Test_Helper_Remote_Inbox_Notifications::class, 'delete' ),
	array(
		'methods' => 'DELETE',
		'args'    => array(
			'id' => array(
				'description' => 'Rest API endpoint.',
				'type'        => 'integer',
				'required'    => true,
			),
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/remote-inbox-notifications/',
	array( WCA_Test_Helper_Remote_Inbox_Notifications::class, 'delete_all_items' ),
	array(
		'methods' => 'DELETE',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/remote-inbox-notifications/import',
	array( WCA_Test_Helper_Remote_Inbox_Notifications::class, 'import' ),
	array(
		'methods' => 'POST',
	)
);


/**
 * Class WCA_Test_Helper_Remote_Inbox_Notifications.
 */
class WCA_Test_Helper_Remote_Inbox_Notifications {

	/**
	 * Delete a filter.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function delete( $request ) {
		global $wpdb;

		$id = $request->get_param( 'id' );
		$wpdb->delete( $wpdb->prefix . 'wc_admin_notes', array( 'note_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'wc_admin_note_actions', array( 'note_id' => $id ) );

		return new WP_REST_RESPONSE( array(
			'success' => true,
			'message' => 'Remote inbox notification deleted.',
		), 200 );
	}

	private static function delete_all() {
		global $wpdb;

		$deleted_note_count   = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_admin_notes" );
		$deleted_action_count = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_admin_note_actions" );
		return array(
			'deleted_note_count'   => $deleted_note_count,
			'deleted_action_count' => $deleted_action_count,
		);
	}

	public function delete_all_items( $request ) {
		$deleted = self::delete_all();
		return new WP_REST_Response( $deleted, 200 );
	}

	public static function get_items( $request ) {
		global $wpdb;
		$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_admin_notes ORDER BY note_id desc", ARRAY_A);
		return new WP_REST_Response( $items, 200 );
	}

	public static function import( $request ) {
		// Get the JSON data from the request body
		$parameters = json_decode(json_encode($request->get_json_params()));
		$stored_statre = RemoteInboxNotificationsEngine::get_stored_state();

		foreach ($parameters as $parameter) {
			SpecRunner::run_spec( $parameter, $stored_statre );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Remote inbox notifications imported.',
		), 200 );
	}
}
