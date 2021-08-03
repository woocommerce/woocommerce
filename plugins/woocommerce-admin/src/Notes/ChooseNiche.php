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
		$note->set_title( __( 'Finding an audience for your business', 'woocommerce-admin' ) );
		$note->set_content( __( 'There’s nothing quite like entrepreneurship! We put together a blog post series — titled From Idea to First Customer — with all the tips, resources, and tools to inspire a great idea, empower you to execute it, and lead to your very first sale.', 'woocommerce-admin' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'choose-niche',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/from-idea-to-first-customer/?utm_source=inbox&utm_medium=product',
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);
		return $note;
	}
}
