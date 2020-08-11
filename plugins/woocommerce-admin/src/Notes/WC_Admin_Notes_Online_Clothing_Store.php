<?php
/**
 * WooCommerce Admin: Start your online clothing store.
 *
 * Adds a note to ask the client if they are considering starting an online
 * clothing store.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Online_Clothing_Store.
 */
class WC_Admin_Notes_Online_Clothing_Store {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-online-clothing-store';

	/**
	 * Returns whether the industries includes fashion-apparel-accessories.
	 *
	 * @param array $industries The industries to search.
	 *
	 * @return bool Whether the industries includes fashion-apparel-accessories.
	 */
	private static function is_in_fashion_industry( $industries ) {
		foreach ( $industries as $industry ) {
			if ( 'fashion-apparel-accessories' === $industry['slug'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the note.
	 */
	public static function get_note() {
		// We want to show the note after two days.
		if ( ! self::wc_admin_active_for( 2 * DAY_IN_SECONDS ) ) {
			return;
		}

		$onboarding_profile = get_option( 'woocommerce_onboarding_profile', array() );

		// Confirm that $onboarding_profile is set.
		if ( empty( $onboarding_profile ) ) {
			return;
		}

		// Make sure that the person who filled out the OBW was not setting up
		// the store for their customer/client.
		if (
			! isset( $onboarding_profile['setup_client'] ) ||
			$onboarding_profile['setup_client']
		) {
			return;
		}

		// We need to show the notification when the industry is
		// fashion/apparel/accessories.
		if ( ! isset( $onboarding_profile['industry'] ) ) {
			return;
		}
		if ( ! self::is_in_fashion_industry( $onboarding_profile['industry'] ) ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Start your online clothing store', 'woocommerce-admin' ) );
		$note->set_content( __( 'Starting a fashion website is exciting but it may seem overwhelming as well. In this article, we\'ll walk you through the setup process, teach you to create successful product listings, and show you how to market to your ideal audience.', 'woocommerce-admin' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'online-clothing-store',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/posts/starting-an-online-clothing-store/?utm_source=inbox',
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);
		return $note;
	}
}
