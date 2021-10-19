<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;

/**
 * A class used to display inbox messages to merchants in the WooCommerce Admin dashboard.
 *
 * @package Automattic\WooCommerce\Blocks
 * @since x.x.x
 */
class InboxNotifications {

	const SURFACE_CART_CHECKOUT_NOTE_NAME          = 'surface_cart_checkout';
	const SURFACE_CART_CHECKOUT_PROBABILITY_OPTION = 'wc_blocks_surface_cart_checkout_probability';
	const PERCENT_USERS_TO_TARGET                  = 50;
	const INELIGIBLE_EXTENSIONS                    = [
		'automatewoo',
		'mailchimp-for-woocommerce',
		'mailpoet',
		'klarna-payments-for-woocommerce',
		'klarna-checkout-for-woocommerce',
		'woocommerce-gutenberg-products-block', // Disallow the notification if the store is using the feature plugin already.
		'woocommerce-all-products-for-subscriptions',
		'woocommerce-bookings',
		'woocommerce-box-office',
		'woocommerce-cart-add-ons',
		'woocommerce-checkout-add-ons',
		'woocommerce-checkout-field-editor',
		'woocommerce-conditional-shipping-and-payments',
		'woocommerce-dynamic-pricing',
		'woocommerce-eu-vat-number',
		'woocommerce-follow-up-emails',
		'woocommerce-gateway-amazon-payments-advanced',
		'woocommerce-gateway-authorize-net-cim',
		'woocommerce-google-analytics-pro',
		'woocommerce-memberships',
		'woocommerce-paypal-payments',
		'woocommerce-pre-orders',
		'woocommerce-product-bundles',
		'woocommerce-shipping-fedex',
		'woocommerce-smart-coupons',
	];
	const ELIGIBLE_COUNTRIES                       = [
		'GB',
		'US',
	];


	/**
	 * Deletes the note.
	 */
	public static function delete_surface_cart_checkout_blocks_notification() {
		Notes::delete_notes_with_name( self::SURFACE_CART_CHECKOUT_NOTE_NAME );
	}

	/**
	 * Creates a notification letting merchants know about the Cart and Checkout Blocks.
	 */
	public static function create_surface_cart_checkout_blocks_notification() {

		// If this is the feature plugin, then we don't need to do this. This should only show when Blocks is bundled
		// with WooCommerce Core.
		if ( Package::feature()->is_feature_plugin_build() ) {
			return;
		}

		if ( ! class_exists( 'Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes' ) ) {
			return;
		}

		if ( ! class_exists( 'WC_Data_Store' ) ) {
			return;
		}

		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::SURFACE_CART_CHECKOUT_NOTE_NAME );

		// Calculate store's eligibility to be shown the notice, starting with whether they have any plugins we know to
		// be incompatible with Blocks. This check is done before checking if the note exists already because we want to
		// delete the note if the merchant activates an ineligible plugin.
		foreach ( self::INELIGIBLE_EXTENSIONS as $extension ) {
			if ( is_plugin_active( $extension . '/' . $extension . '.php' ) ) {

				// Delete the notification here, we shouldn't show it if it's not going to work with the merchant's site.
				self::delete_surface_cart_checkout_blocks_notification();
				return;
			}
		}

		foreach ( (array) $note_ids as $note_id ) {
			$note = Notes::get_note( $note_id );

			// Return now because the note already exists.
			if ( $note->get_name() === self::SURFACE_CART_CHECKOUT_NOTE_NAME ) {
				return;
			}
		}

		// Next check the store is located in one of the eligible countries.
		$raw_country = get_option( 'woocommerce_default_country' );
		$country     = explode( ':', $raw_country )[0];
		if ( ! in_array( $country, self::ELIGIBLE_COUNTRIES, true ) ) {
			return;
		}

		// Pick a random number between 1 and 100 and add this to the wp_options table. This can then be used to target
		// a percentage of users. We do this here so we target a truer percentage of eligible users than if we did it
		// before checking plugins/country.
		$existing_probability = get_option( self::SURFACE_CART_CHECKOUT_PROBABILITY_OPTION );
		if ( false === $existing_probability ) {
			$existing_probability = wp_rand( 0, 100 );
			add_option( self::SURFACE_CART_CHECKOUT_PROBABILITY_OPTION, $existing_probability );
		}

		// Finally, check if the store's generated % chance is below the % of users we want to surface this to.
		if ( $existing_probability > self::PERCENT_USERS_TO_TARGET ) {
			return;
		}

		// At this point, the store meets all the criteria to be shown the notice! Woo!
		$note = new Note();
		$note->set_title(
			__(
				'Introducing the Cart and Checkout blocks!',
				'woocommerce'
			)
		);
		$note->set_content(
			__(
				"Increase your store's revenue with the conversion optimized Cart & Checkout WooCommerce blocks available in the WooCommerce Blocks extension.",
				'woocommerce'
			)
		);
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_source( 'woo-gutenberg-products-block' );
		$note->set_name( self::SURFACE_CART_CHECKOUT_NOTE_NAME );
		$note->add_action(
			'learn_more',
			'Learn More',
			'https://woocommerce.com/checkout-blocks/'
		);
		$note->save();

	}
}
