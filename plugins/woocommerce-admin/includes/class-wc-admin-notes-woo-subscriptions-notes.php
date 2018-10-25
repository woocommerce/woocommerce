<?php
/**
 * WooCommerce Admin (Dashboard) WooCommerce.com Extension Subscriptions Note Provider.
 *
 * Adds notes to the merchant's inbox concerning WooCommerce.com extension subscriptions.
 *
 * @package WooCommerce Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Woo_Subscriptions_Notes
 */
class WC_Admin_Notes_Woo_Subscriptions_Notes {
	const CONNECTION_NOTE_NAME   = 'wc-admin-wc-helper-connection';
	const SUBSCRIPTION_NOTE_NAME = 'wc-admin-wc-helper-subscription';

	/**
	 * Hook all the things.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'check_connection' ) );
		add_action( 'admin_init', array( $this, 'refresh_subscription_notes' ) ); // TODO Do not commit this line.
		add_action( 'update_option_woocommerce_helper_data', array( $this, 'update_option_woocommerce_helper_data' ), 10, 2 );
		// TODO : prune_inactive_subscription_notes daily.
		// TODO : refresh_subscription_notes daily.
	}

	/**
	 * Reacts to changes in the helper option.
	 *
	 * @param array $old_value The previous value of the option.
	 * @param array $value The new value of the option.
	 */
	public function update_option_woocommerce_helper_data( $old_value, $value ) {
		if ( ! is_array( $old_value ) ) {
			$old_value = array();
		}
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$old_auth  = array_key_exists( 'auth', $old_value ) ? $old_value['auth'] : array();
		$new_auth  = array_key_exists( 'auth', $value ) ? $value['auth'] : array();
		$old_token = array_key_exists( 'access_token', $old_auth ) ? $old_auth['access_token'] : '';
		$new_token = array_key_exists( 'access_token', $new_auth ) ? $new_auth['access_token'] : '';

		// The site just disconnected.
		if ( ! empty( $old_token ) && empty( $new_token ) ) {
			$this->remove_notes();
			$this->add_no_connection_note();
			return;
		}

		// The site just connected.
		if ( empty( $old_token ) && ! empty( $new_token ) ) {
			$this->remove_notes();
			$this->refresh_subscription_notes();
			return;
		}

		// Something else changed. Refresh all the things.
		$this->prune_inactive_subscription_notes();
		$this->refresh_subscription_notes();
	}

	/**
	 * Checks the connection. Adds a note (as necessary) if there is no connection.
	 */
	public function check_connection() {
		if ( ! $this->is_connected() ) {
			$data_store = WC_Data_Store::load( 'admin-note' );
			$note_ids   = $data_store->get_notes_with_name( self::CONNECTION_NOTE_NAME );
			if ( ! empty( $note_ids ) ) {
				return;
			}

			$this->remove_notes();
			$this->add_no_connection_note();
		}
	}

	/**
	 * Whether or not we think the site is currently connected to WooCommerce.com.
	 *
	 * @return bool
	 */
	public function is_connected() {
		$auth = WC_Helper_Options::get( 'auth' );
		return ( ! empty( $auth['access_token'] ) );
	}

	/**
	 * Returns the WooCommerce.com provided site ID for this site.
	 *
	 * @return int|false
	 */
	public function get_connected_site_id() {
		if ( ! $this->is_connected() ) {
			return false;
		}

		$auth = WC_Helper_Options::get( 'auth' );
		return absint( $auth['site_id'] );
	}

	/**
	 * Returns an array of product_ids whose subscriptions are active on this site.
	 *
	 * @return array
	 */
	public function get_subscription_active_product_ids() {
		$site_id = $this->get_connected_site_id();
		if ( ! $site_id ) {
			return array();
		}

		$product_ids = array();

		if ( $this->is_connected() ) {
			$subscriptions = WC_Helper::get_subscriptions();

			foreach ( (array) $subscriptions as $subscription ) {
				if ( in_array( $site_id, $subscription['connections'], true ) ) {
					$product_ids[] = $subscription['product_id'];
				}
			}
		}

		return $product_ids;
	}

	/**
	 * Clears all connection or subscription notes.
	 */
	public function remove_notes() {
		WC_Admin_Notes::delete_notes_with_name( self::CONNECTION_NOTE_NAME );
		WC_Admin_Notes::delete_notes_with_name( self::SUBSCRIPTION_NOTE_NAME );
	}

	/**
	 * Adds a note prompting to connect to WooCommerce.com.
	 */
	public function add_no_connection_note() {
		$note = new WC_Admin_Note();
		$note->set_title( __( 'Connect to WooCommerce.com', 'wc-admin' ) );
		$note->set_content( __( 'Connect to get important product notifications and updates.', 'wc-admin' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'info' );
		$note->set_name( self::CONNECTION_NOTE_NAME );
		$note->set_source( 'wc-admin' );
		$note->add_action(
			'connect',
			__( 'Connect', 'wc-admin' ),
			'?page=wc-addons&section=helper'
		);
		$note->save();
	}

	/**
	 * Gets the product_id (if any) associated with a note.
	 *
	 * @param WC_Admin_Note $note The note object to interrogate.
	 * @return string
	 */
	public function get_product_id_from_subscription_note( &$note ) {
		$content_data = $note->get_content_data();

		if ( array_key_exists( 'product_id', $content_data ) ) {
			return $content_data['product_id'];
		}

		return '';
	}

	/**
	 * Removes notes for product_ids no longer active on this site.
	 */
	public function prune_inactive_subscription_notes() {
		error_log( 'in prune_inactive_subscription_notes' );
		$active_product_ids = $this->get_subscription_active_product_ids();

		$data_store = WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::SUBSCRIPTION_NOTE_NAME );

		foreach ( (array) $note_ids as $note_id ) {
			error_log( "in prune_inactive_subscription_notes - evaluating note ID $note_id" );
			$note       = WC_Admin_Notes::get_note( $note_id );
			$product_id = $this->get_product_id_from_subscription_note( $note );
			if ( ! empty( $product_id ) ) {
				if ( ! in_array( $product_id, $active_product_ids, true ) ) {
					error_log( "product $product_id is no longer active on this site. deleting note $note_id" );
					$note->delete();
				}
			}
		}
	}

	/**
	 * For each active subscription on this site, checks the expiration date and creates/updates notes.
	 */
	public function refresh_subscription_notes() {
		if ( ! $this->is_connected() ) {
			return;
		}

		error_log( 'in refresh_subscription_notes' );
		$subscriptions      = WC_Helper::get_subscriptions();
		$active_product_ids = $this->get_subscription_active_product_ids();

		foreach ( (array) $active_product_ids as $active_product_id ) {
			// Find that products subscription.
			// Check the expiration date.
			// Is it expiring soon?
			//  Do we have a note already for it?
			//   Yes? Update it to show the number of days remaining.
			//   No? Create one.
			// Has it expired?
			//  Do we have a note already for it?
			//   Yes? Does the note already indicate the extension has expired?
			//    Yes? Do nothing.
			//    No? Update the date to indicate it has expired.
			//   No note? Create one, indicate the extension has expired.
			// Has it neither expired nor is expiring soon?
			//  Do we have a note already for it?
			//   Yes? Delete any note for that extension.
		}
	}
}

new WC_Admin_Notes_Woo_Subscriptions_Notes();
