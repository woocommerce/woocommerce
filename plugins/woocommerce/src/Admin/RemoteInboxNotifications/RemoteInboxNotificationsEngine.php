<?php
/**
 * Handles running specs
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Admin\PluginsProvider\PluginsProvider;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\RemoteSpecs\RemoteSpecsEngine;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\StoredStateSetupForProducts;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

/**
 * Remote Inbox Notifications engine.
 * This goes through the specs and runs (creates admin notes) for those
 * specs that are able to be triggered.
 */
class RemoteInboxNotificationsEngine extends RemoteSpecsEngine {
	use AccessiblePrivateMethods;

	const STORED_STATE_OPTION_NAME = 'wc_remote_inbox_notifications_stored_state';
	const WCA_UPDATED_OPTION_NAME  = 'wc_remote_inbox_notifications_wca_updated';

	/**
	 * Initialize the engine.
	 * phpcs:disable WooCommerce.Functions.InternalInjectionMethod.MissingFinal
	 * phpcs:disable WooCommerce.Functions.InternalInjectionMethod.MissingInternalTag
	 */
	public static function init() {
		// Init things that need to happen before admin_init.
		add_action( 'init', array( __CLASS__, 'on_init' ), 0, 0 );

		// Continue init via admin_init.
		add_action( 'admin_init', array( __CLASS__, 'on_admin_init' ) );

		// Trigger when the profile data option is updated (during onboarding).
		add_action(
			'update_option_' . OnboardingProfile::DATA_OPTION,
			array( __CLASS__, 'update_profile_option' ),
			10,
			2
		);

		// Hook into WCA updated. This is hooked up here rather than in
		// on_admin_init because that runs too late to hook into the action.
		add_action(
			'woocommerce_run_on_woocommerce_admin_updated',
			array( __CLASS__, 'run_on_woocommerce_admin_updated' )
		);
		add_action(
			'woocommerce_updated',
			function () {
				$next_hook = WC()->queue()->get_next(
					'woocommerce_run_on_woocommerce_admin_updated',
					array(),
					'woocommerce-remote-inbox-engine'
				);
				if ( null === $next_hook ) {
					WC()->queue()->schedule_single(
						time(),
						'woocommerce_run_on_woocommerce_admin_updated',
						array(),
						'woocommerce-remote-inbox-engine'
					);
				}
			}
		);

		add_filter( 'woocommerce_get_note_from_db', array( __CLASS__, 'get_note_from_db' ), 10, 1 );
		self::add_filter( 'woocommerce_debug_tools', array( __CLASS__, 'add_debug_tools' ) );
		self::add_action(
			'wp_ajax_woocommerce_json_inbox_notifications_search',
			array( __CLASS__, 'ajax_action_inbox_notification_search' )
		);
	}

	/**
	 * This is triggered when the profile option is updated and if the
	 * profiler is being completed, triggers a run of the engine.
	 *
	 * @param mixed $old_value Old value.
	 * @param mixed $new_value New value.
	 */
	public static function update_profile_option( $old_value, $new_value ) {
		// Return early if we're not completing the profiler.
		if (
			( isset( $old_value['completed'] ) && $old_value['completed'] ) ||
			! isset( $new_value['completed'] ) ||
			! $new_value['completed']
		) {
			return;
		}

		self::run();
	}

	/**
	 * Init is continued via admin_init so that WC is loaded when the product
	 * query is used, otherwise the query generates a "0 = 1" in the WHERE
	 * condition and thus doesn't return any results.
	 */
	public static function on_admin_init() {
		add_action( 'activated_plugin', array( __CLASS__, 'run' ) );
		add_action( 'deactivated_plugin', array( __CLASS__, 'run_on_deactivated_plugin' ), 10, 1 );
		StoredStateSetupForProducts::admin_init();

		// Pre-fetch stored state so it has the correct initial values.
		self::get_stored_state();
	}

	/**
	 * An init hook is used here so that StoredStateSetupForProducts can set
	 * up a hook that gets triggered by action-scheduler - this is needed
	 * because the admin_init hook doesn't get triggered by WP Cron.
	 */
	public static function on_init() {
		StoredStateSetupForProducts::init();
	}

	/**
	 * Go through the specs and run them.
	 */
	public static function run() {
		$specs = RemoteInboxNotificationsDataSourcePoller::get_instance()->get_specs_from_data_sources();

		if ( false === $specs || ! is_countable( $specs ) || count( $specs ) === 0 ) {
			return;
		}

		$stored_state = self::get_stored_state();
		$errors       = array();

		foreach ( $specs as $spec ) {
			$error = SpecRunner::run_spec( $spec, $stored_state );
			if ( isset( $error ) ) {
				$errors[] = $error;
			}
		}

		if ( count( $errors ) > 0 ) {
			self::log_errors( $errors );
		}
	}

	/**
	 * Set an option indicating that WooCommerce Admin has just been updated,
	 * run the specs, then clear that option. This lets the
	 * WooCommerceAdminUpdatedRuleProcessor trigger on WCA update.
	 */
	public static function run_on_woocommerce_admin_updated() {
		update_option( self::WCA_UPDATED_OPTION_NAME, true, false );

		self::run();

		update_option( self::WCA_UPDATED_OPTION_NAME, false, false );
	}

	/**
	 * Gets the stored state option, and does the initial set up if it doesn't
	 * already exist.
	 *
	 * @return object The stored state option.
	 */
	public static function get_stored_state() {
		$stored_state = get_option( self::STORED_STATE_OPTION_NAME );

		if ( false === $stored_state ) {
			$stored_state = new \stdClass();

			$stored_state = StoredStateSetupForProducts::init_stored_state(
				$stored_state
			);

			add_option(
				self::STORED_STATE_OPTION_NAME,
				$stored_state,
				'',
				false
			);
		}

		return $stored_state;
	}

	/**
	 * The deactivated_plugin hook happens before the option is updated
	 * (https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/plugin.php#L826)
	 * so this captures the deactivated plugin path and pushes it into the
	 * PluginsProvider.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory.
	 */
	public static function run_on_deactivated_plugin( $plugin ) {
		PluginsProvider::set_deactivated_plugin( $plugin );
		self::run();
	}

	/**
	 * Update the stored state option.
	 *
	 * @param object $stored_state The stored state.
	 */
	public static function update_stored_state( $stored_state ) {
		update_option( self::STORED_STATE_OPTION_NAME, $stored_state, false );
	}

	/**
	 * Get the note. This is used to display localized note.
	 *
	 * @param Note $note_from_db The note object created from db.
	 *
	 * @return Note The note.
	 */
	public static function get_note_from_db( $note_from_db ) {
		if ( ! $note_from_db instanceof Note || get_user_locale() === $note_from_db->get_locale() ) {
			return $note_from_db;
		}
		$specs = RemoteInboxNotificationsDataSourcePoller::get_instance()->get_specs_from_data_sources();
		foreach ( $specs as $spec ) {
			if ( $spec->slug !== $note_from_db->get_name() ) {
				continue;
			}
			$locale = SpecRunner::get_locale( $spec->locales, true );
			if ( null === $locale ) {
				// No locale found, so don't update the note.
				break;
			}

			$localized_actions = SpecRunner::get_actions( $spec );

			// Manually copy the action id from the db to the localized action, since they were not being provided.
			foreach ( $localized_actions as $localized_action ) {
				$action = $note_from_db->get_action( $localized_action->name );
				if ( $action ) {
					$localized_action->id = $action->id;
				}
			}

			$note_from_db->set_title( $locale->title );
			$note_from_db->set_content( $locale->content );
			$note_from_db->set_actions( $localized_actions );
		}

		return $note_from_db;
	}

	/**
	 * Add the debug tools to the WooCommerce debug tools (WooCommerce > Status > Tools).
	 *
	 * @param array $tools a list of tools.
	 *
	 * @return mixed
	 */
	private static function add_debug_tools( $tools ) {
		// Check if the feature flag is disabled.
		if ( ! Features::is_enabled( 'remote-inbox-notifications' ) ) {
			return false;
		}

		// Check if the site has opted out of marketplace suggestions.
		if ( get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) !== 'yes' ) {
			return false;
		}

		$tools['refresh_remote_inbox_notifications'] = array(
			'name'     => __( 'Refresh Remote Inbox Notifications', 'woocommerce' ),
			'button'   => __( 'Refresh', 'woocommerce' ),
			'desc'     => __( 'This will refresh the remote inbox notifications', 'woocommerce' ),
			'callback' => function () {
				RemoteInboxNotificationsDataSourcePoller::get_instance()->read_specs_from_data_sources();
				RemoteInboxNotificationsEngine::run();

				return __( 'Remote inbox notifications have been refreshed', 'woocommerce' );
			},
		);

		$tools['delete_inbox_notification'] = array(
			'name'     => __( 'Delete an Inbox Notification', 'woocommerce' ),
			'button'   => __( 'Delete', 'woocommerce' ),
			'desc'     => __( 'This will delete an inbox notification by slug', 'woocommerce' ),
			'selector' => array(
				'description'   => __( 'Select an inbox notification to delete:', 'woocommerce' ),
				'class'         => 'wc-product-search',
				'search_action' => 'woocommerce_json_inbox_notifications_search',
				'name'          => 'delete_inbox_notification_note_id',
				'placeholder'   => esc_attr__( 'Search for an inbox notification&hellip;', 'woocommerce' ),
			),
			'callback' => function () {
				check_ajax_referer( 'debug_action', '_wpnonce' );

				if ( ! isset( $_GET['delete_inbox_notification_note_id'] ) ) {
					return __( 'No inbox notification selected', 'woocommerce' );
				}
				$note_id = wc_clean( sanitize_text_field( wp_unslash( $_GET['delete_inbox_notification_note_id'] ) ) );
				$note = Notes::get_note( $note_id );

				if ( ! $note ) {
					return __( 'Inbox notification not found', 'woocommerce' );
				}

				$note->delete( true );
				return __( 'Inbox notification has been deleted', 'woocommerce' );
			},
		);

		return $tools;
	}

	/**
	 * Add ajax action for remote inbox notification search.
	 *
	 * @return void
	 */
	private static function ajax_action_inbox_notification_search() {
		global $wpdb;

		check_ajax_referer( 'search-products', 'security' );

		if ( ! isset( $_GET['term'] ) ) {
			wp_send_json( array() );
		}

		$search  = wc_clean( sanitize_text_field( wp_unslash( $_GET['term'] ) ) );
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT note_id, name FROM {$wpdb->prefix}wc_admin_notes WHERE name LIKE %s",
				'%' . $wpdb->esc_like( $search ) . '%'
			)
		);
		$rows    = array();
		foreach ( $results as $result ) {
			$rows[ $result->note_id ] = $result->name;
		}
		wp_send_json( $rows );
	}
}
