<?php
/**
 * WooCommerce: Db update note.
 *
 * Adds a note to complete the WooCommerce db update after the upgrade in the WC Admin context.
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

use \Automattic\Jetpack\Constants;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Note;

/**
 * WC_Notes_Run_Db_Update.
 */
class WC_Notes_Run_Db_Update {
	const NOTE_NAME = 'wc-update-db-reminder';

	/**
	 * Attach hooks.
	 */
	public function __construct() {
		// If the old notice gets dismissed, also hide this new one.
		add_action( 'woocommerce_hide_update_notice', array( __CLASS__, 'set_notice_actioned' ) );

		// Not using Jetpack\Constants here as it can run before 'plugin_loaded' is done.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX
			|| defined( 'DOING_CRON' ) && DOING_CRON
			|| ! is_admin() ) {
			return;
		}

		add_action( 'current_screen', array( __CLASS__, 'show_reminder' ) );
	}

	/**
	 * Get current notice id from the database.
	 *
	 * Retrieves the first notice of this type.
	 *
	 * @return int|void Note id or null in case no note was found.
	 */
	private static function get_current_notice() {
		try {
			$data_store = \WC_Data_Store::load( 'admin-note' );
		} catch ( Exception $e ) {
			return;
		}
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );

		if ( empty( $note_ids ) ) {
			return;
		}

		return current( $note_ids );
	}

	/**
	 * Set this notice to an actioned one, so that it's no longer displayed.
	 */
	public static function set_notice_actioned() {
		$note_id = self::get_current_notice();

		if ( ! $note_id ) {
			return;
		}

		$note = new WC_Admin_Note( $note_id );
		$note->set_status( WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED );
		$note->save();
	}

	/**
	 * Check whether the note is up to date for a fresh display.
	 *
	 * The check tests if
	 *  - actions are set up for the first 'Update database' notice, and
	 *  - URL for note's action is equal to the given URL (to check for potential nonce update).
	 *
	 * @param WC_Admin_Note $note Note to check.
	 * @param string        $update_url  URL to check the note against.
	 * @return bool
	 */
	private static function note_up_to_date( $note, $update_url ) {
		$actions = $note->get_actions();
		if ( 2 === count( array_intersect( wp_list_pluck( $actions, 'name' ), array( 'update-db_run', 'update-db_learn-more' ) ) )
			&& in_array( $update_url, wp_list_pluck( $actions, 'query' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Create and set up the first (out of 3) 'Database update needed' notice and store it in the database.
	 *
	 * If a $note_id is given, the method updates the note instead of creating a new one.
	 *
	 * @param integer $note_id Note db record to update.
	 * @return int Created/Updated note id
	 */
	private static function update_needed_notice( $note_id = null ) {
		$update_url = html_entity_decode(
			wp_nonce_url(
				add_query_arg( 'do_update_woocommerce', 'true', admin_url( 'admin.php?page=wc-settings' ) ),
				'wc_db_update',
				'wc_db_update_nonce'
			)
		);

		if ( $note_id ) {
			$note = new WC_Admin_Note( $note_id );
		} else {
			$note = new WC_Admin_Note();
		}

		// Check if the note needs to be updated (e.g. expired nonce or different note type stored in the previous run).
		if ( self::note_up_to_date( $note, $update_url ) ) {
			return $note_id;
		}

		$note->set_title( __( 'WooCommerce database update required', 'woocommerce' ) );
		$note->set_content(
			__( 'WooCommerce has been updated! To keep things running smoothly, we have to update your database to the newest version.', 'woocommerce' )
			/* translators: %1$s: opening <a> tag %2$s: closing </a> tag*/
			. sprintf( ' ' . esc_html__( 'The database update process runs in the background and may take a little while, so please be patient. Advanced users can alternatively update via %1$sWP CLI%2$s.', 'woocommerce' ), '<a href="https://github.com/woocommerce/woocommerce/wiki/Upgrading-the-database-using-WP-CLI">', '</a>' )
		);
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_UPDATE );
		$note->set_icon( 'info' );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-core' );
		// In case db version is out of sync with WC version or during the next update, the notice needs to show up again,
		// so set it to unactioned.
		$note->set_status( WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED );

		// Set new actions.
		$note->clear_actions();
		$note->add_action(
			'update-db_run',
			__( 'Update WooCommerce Database', 'woocommerce' ),
			$update_url,
			'unactioned',
			true
		);
		$note->add_action(
			'update-db_learn-more',
			__( 'Learn more about updates', 'woocommerce' ),
			'https://docs.woocommerce.com/document/how-to-update-woocommerce/',
			'unactioned',
			false
		);

		return $note->save();
	}

	/**
	 * Update the existing note with $note_id with information about the db upgrade being in progress.
	 *
	 * This is the second out of 3 notices displayed to the user.
	 *
	 * @param int $note_id Note id to update.
	 */
	private static function update_in_progress_notice( $note_id ) {
		// Same actions as in includes/admin/views/html-notice-updating.php.
		$pending_actions_url = admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=woocommerce_run_update&status=pending' );
		$cron_disabled       = Constants::is_true( 'DISABLE_WP_CRON' );
		$cron_cta            = $cron_disabled ? __( 'You can manually run queued updates here.', 'woocommerce' ) : __( 'View progress →', 'woocommerce' );

		$note = new WC_Admin_Note( $note_id );
		$note->set_title( __( 'WooCommerce database update in progress', 'woocommerce' ) );
		$note->set_content( __( 'WooCommerce is updating the database in the background. The database update process may take a little while, so please be patient.', 'woocommerce' ) );

		$note->clear_actions();
		$note->add_action(
			'update-db_see-progress',
			$cron_cta,
			$pending_actions_url,
			'unactioned',
			false
		);

		$note->save();
	}

	/**
	 * Update the existing note with $note_id with information that db upgrade is done.
	 *
	 * This is the last notice (3 out of 3 notices) displayed to the user.
	 *
	 * @param int $note_id Note id to update.
	 */
	private static function update_done_notice( $note_id ) {
		$hide_notices_url = html_entity_decode( // to convert &amp;s to normal &, otherwise produces invalid link.
			wp_nonce_url(
				add_query_arg(
					'wc-hide-notice',
					'update',
					admin_url( 'admin.php?page=wc-settings' )
				),
				'woocommerce_hide_notices_nonce',
				'_wc_notice_nonce'
			)
		);

		$note = new WC_Admin_Note( $note_id );
		$note->set_title( __( 'WooCommerce database update done', 'woocommerce' ) );
		$note->set_content( __( 'WooCommerce database update complete. Thank you for updating to the latest version!', 'woocommerce' ) );

		$actions = $note->get_actions();
		if ( ! in_array( 'update-db_done', wp_list_pluck( $actions, 'name' ) ) ) {
			$note->clear_actions();
			$note->add_action(
				'update-db_done',
				__( 'Thanks!', 'woocommerce' ),
				$hide_notices_url,
				'actioned',
				true
			);

			$note->save();
		}
	}

	/**
	 * Return true if db update notice should be shown, false otherwise.
	 *
	 * If the db needs an update, the notice should be always shown.
	 * If the db does not need an update, but the notice has *not* been actioned (i.e. after the db update, when
	 * store owner hasn't acknowledged the successful db update), still show the notice.
	 * If the db does not need an update, and the notice has been actioned, then notice should *not* be shown.
	 * The same is true if the db does not need an update and the notice does not exist.
	 *
	 * @return bool
	 */
	private static function should_show_notice() {
		if ( ! \WC_Install::needs_db_update() ) {
			try {
				$data_store = \WC_Data_Store::load( 'admin-note' );
			} catch ( Exception $e ) {
				// Bail out in case of incorrect use.
				return false;
			}
			$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );

			if ( ! empty( $note_ids ) ) {
				// Db update not needed && note actioned -> don't show it.
				$note = new WC_Admin_Note( $note_ids[0] );
				if ( $note::E_WC_ADMIN_NOTE_ACTIONED === $note->get_status() ) {
					return false;
				}
			} else {
				// Db update not needed && note does not exist -> don't show it.
				return false;
			}
		}

		return true;
	}

	/**
	 * Prepare the correct content of the db update note to be displayed by WC Admin.
	 *
	 * This one gets called on each page load, so try to bail quickly.
	 */
	public static function show_reminder() {
		if ( ! self::should_show_notice() ) {
			return;
		}

		$note_id = self::get_current_notice();

		if ( \WC_Install::needs_db_update() && empty( $note_id ) ) {
			// Db needs update && no notice exists -> create one.
			$note_id = self::update_needed_notice();
		}

		if ( \WC_Install::needs_db_update() ) {
			$next_scheduled_date = WC()->queue()->get_next( 'woocommerce_run_update_callback', null, 'woocommerce-db-updates' );

			if ( $next_scheduled_date || ! empty( $_GET['do_update_woocommerce'] ) ) { // WPCS: input var ok, CSRF ok.
				self::update_in_progress_notice( $note_id );
			} else {
				self::update_needed_notice( $note_id );
			}
		} else {
			\WC_Install::update_db_version();
			self::update_done_notice( $note_id );
		}
	}

}
