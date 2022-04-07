<?php
/**
 * Installation related functions and actions.
 */

namespace Automattic\WooCommerce\Internal\Admin;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Admin\Notes\Notes;
/**
 * Install Class.
 */
class Install {
	/**
	 * Plugin version option name.
	 */
	const VERSION_OPTION = 'woocommerce_admin_version';

	/**
	 * Delete obsolete notes.
	 */
	public static function delete_obsolete_notes() {
		$obsolete_notes_names = array(
			'wc-admin-welcome-note',
			'wc-admin-store-notice-setting-moved',
			'wc-admin-store-notice-giving-feedback',
			'wc-admin-learn-more-about-product-settings',
			'wc-admin-onboarding-profiler-reminder',
			'wc-admin-historical-data',
			'wc-admin-review-shipping-settings',
			'wc-admin-home-screen-feedback',
			'wc-admin-effortless-payments-by-mollie',
			'wc-admin-google-ads-and-marketing',
			'wc-admin-marketing-intro',
			'wc-admin-draw-attention',
			'wc-admin-need-some-inspiration',
			'wc-admin-choose-niche',
			'wc-admin-start-dropshipping-business',
			'wc-admin-filter-by-product-variations-in-reports',
			'wc-admin-learn-more-about-variable-products',
			'wc-admin-getting-started-ecommerce-webinar',
			'wc-admin-navigation-feedback',
			'wc-admin-navigation-feedback-follow-up',
		);

		$additional_obsolete_notes_names = apply_filters(
			'woocommerce_admin_obsolete_notes_names',
			array()
		);

		if ( is_array( $additional_obsolete_notes_names ) ) {
			$obsolete_notes_names = array_merge(
				$obsolete_notes_names,
				$additional_obsolete_notes_names
			);
		}

		Notes::delete_notes_with_name( $obsolete_notes_names );
	}
}
