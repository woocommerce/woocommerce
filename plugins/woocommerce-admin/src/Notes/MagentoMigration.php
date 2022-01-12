<?php
/**
 * WooCommerce Admin note on how to migrate from Magento.
 */

namespace Automattic\WooCommerce\Admin\Notes;

use Automattic\WooCommerce\Admin\Features\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * MagentoMigration
 */
class MagentoMigration {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-magento-migration';

	/**
	 * Attach hooks.
	 */
	public function __construct() {
		add_action( 'update_option_' . Onboarding::PROFILE_DATA_OPTION, array( __CLASS__, 'possibly_add_note' ) );
		add_action( 'woocommerce_admin_magento_migration_note', array( __CLASS__, 'save_note' ) );
	}

	/**
	 * Add the note if it passes predefined conditions.
	 */
	public static function possibly_add_note() {
		$onboarding_profile = get_option( Onboarding::PROFILE_DATA_OPTION, array() );

		if ( empty( $onboarding_profile ) ) {
			return;
		}

		if (
			! isset( $onboarding_profile['other_platform'] ) ||
			'magento' !== $onboarding_profile['other_platform']
		) {
			return;
		}

		if (
			! isset( $onboarding_profile['setup_client'] ) ||
			$onboarding_profile['setup_client']
		) {
			return;
		}

		WC()->queue()->schedule_single( time() + ( 5 * MINUTE_IN_SECONDS ), 'woocommerce_admin_magento_migration_note' );
	}

	/**
	 * Save the note to the database.
	 */
	public static function save_note() {
		$note = self::get_note();

		if ( self::note_exists() ) {
			return;
		}

		$note->save();
	}

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$note = new Note();

		$note->set_title( __( 'How to Migrate from Magento to WooCommerce', 'woocommerce-admin' ) );
		$note->set_content( __( 'Changing platforms might seem like a big hurdle to overcome, but it is easier than you might think to move your products, customers, and orders to WooCommerce. This article will help you with going through this process.', 'woocommerce-admin' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/posts/how-migrate-from-magento-to-woocommerce/?utm_source=inbox'
		);

		return $note;
	}
}
