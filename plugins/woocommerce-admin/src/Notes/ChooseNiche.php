<?php
/**
 * WooCommerce Admin: Choose a niche note.
 *
 * Adds a note to show the client how to choose a niche for their store.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Choose_Niche.
 */
class ChooseNiche {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-choose-niche';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$onboarding_profile = get_option( 'woocommerce_onboarding_profile', array() );

		// Confirm that $onboarding_profile is set.
		if ( empty( $onboarding_profile ) ) {
			return;
		}

		// Make sure that the person who filled out the OBW was not setting up the store for their customer/client.
		if (
			! isset( $onboarding_profile['setup_client'] ) ||
			$onboarding_profile['setup_client']
		) {
			return;
		}

		// Make sure that the product count is set in the onboarding profile.
		if ( ! isset( $onboarding_profile['product_count'] ) ) {
			return;
		}

		// Make sure that the revenue is set in the onboarding profile.
		if ( ! isset( $onboarding_profile['revenue'] ) ) {
			return;
		}

		// We need to show the notification when product number is 0 or the revenue is 'none' or 'up to 2500'.
		if (
			0 !== (int) $onboarding_profile['product_count'] &&
			'none' !== $onboarding_profile['revenue'] &&
			'up-to-2500' !== $onboarding_profile['revenue']
		) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'How to choose a niche for your online store', 'woocommerce-admin' ) );
		$note->set_content( __( 'Your niche defines the products and services you develop. It directs your marketing. It focuses your attention on specific problems facing your customers. It differentiates you from the competition. Learn more about the five guiding principles to define your niche.', 'woocommerce-admin' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'choose-niche',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/posts/how-to-choose-a-niche-online-business/?utm_source=inbox',
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);
		return $note;
	}
}
