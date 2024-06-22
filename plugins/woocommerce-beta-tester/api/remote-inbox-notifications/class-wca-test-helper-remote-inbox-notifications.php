<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\SpecRunner;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsDataSourcePoller;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\GetRuleProcessor;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator;

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
	'/remote-inbox-notifications/(?P<name>(.*)+)/test',
	array( WCA_Test_Helper_Remote_Inbox_Notifications::class, 'test' ),
	array(
		'methods' => 'GET',
		'args'    => array(
			'name' => array(
				'description' => 'Note name.',
				'type'        => 'string',
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

		return new WP_REST_RESPONSE(
			array(
				'success' => true,
				'message' => 'Remote inbox notification deleted.',
			),
			200
		);
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

	private static function get_notes_to_exclude() {
		$vars            = array( 'other_note_classes', 'note_classes_to_added_or_updated' );
		$note_names      = array();
		$reflectionClass = new ReflectionClass( '\Automattic\WooCommerce\Internal\Admin\Events' );
		foreach ( $vars as $var ) {
			$property = $reflectionClass->getProperty( $var );
			$property->setAccessible( true );
			$notes      = $property->getValue();
			$note_names = array_merge(
				$note_names,
				array_map(
					function ( $note ) {
						return $note::NOTE_NAME;
					},
					$notes
				)
			);
		}

		return $note_names;
	}

	public static function get_items( $request ) {
		global $wpdb;
		$items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wc_admin_notes ORDER BY note_id desc", ARRAY_A );

		$notes_added_via_classes = self::get_notes_to_exclude();
		$items                   = array_filter(
			$items,
			function ( $item ) use ( $notes_added_via_classes ) {
				return ! in_array( $item['name'], $notes_added_via_classes );
			}
		);

		return new WP_REST_Response( array_values( $items ), 200 );
	}

	public static function get_transient_name() {
		return 'woocommerce_admin_' . RemoteInboxNotificationsDataSourcePoller::ID . '_specs';
	}

	public static function test( $request ) {
		$name          = $request->get_param( 'name' );
		$notifications = get_transient( static::get_transient_name() );
		$locale        = get_locale();

		if ( ! isset( $notifications[ $locale ][ $name ] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => "'{$name}' was not found in the latest remote inbox notification transient. Please re-run the import command.",
				),
				200
			);
		}

		$spec           = $notifications[ $locale ][ $name ];
		$test           = true;
		$failed_rules   = array();
		$rule_processor = new GetRuleProcessor();
		foreach ( $spec->rules as $rule ) {
			if ( ! is_object( $rule ) ) {
				$test = false;
				break;
			}
			$processor        = $rule_processor->get_processor( $rule->type );
			$processor_result = $processor->process( $rule, null );
			if ( ! $processor_result ) {
				$test           = false;
				$failed_rules[] = $rule;
			}
		}

		$message = $test ? $name . ': All rules passed sucessfully' : $failed_rules;

		if ( $test ) {
			$stored_state = RemoteInboxNotificationsEngine::get_stored_state();
			SpecRunner::run_spec( $spec, $stored_state );
		}

		return new WP_REST_Response(
			array(
				'success' => $test,
				'message' => $message,
			),
			200
		);
	}

	public static function import( $request ) {
		// Get the JSON data from the request body
		$specs         = json_decode( json_encode( $request->get_json_params() ) );
		$stored_statre = RemoteInboxNotificationsEngine::get_stored_state();

		$transient = get_transient( static::get_transient_name() );

		foreach ( $specs as $spec ) {
			SpecRunner::run_spec( $spec, $stored_statre );
			if ( isset( $spec->locales ) && is_array( $spec->locales ) ) {
				foreach ( $spec->locales as $locale ) {
					$transient[ $locale->locale ][ $spec->slug ] = $spec;
				}
			}
		}

		set_transient( static::get_transient_name(), $transient );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Remote inbox notifications imported.',
			),
			200
		);
	}
}
