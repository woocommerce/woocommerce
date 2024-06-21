/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

// @todo: Implement the right copy for the pattern categories: https://github.com/woocommerce/woocommerce/issues/48381
export const PATTERN_CATEGORIES = {
	intro: {
		label: __( 'intro', 'woocommerce' ),
		description: __(
			'Welcome shoppers to your store with one of our introductory patterns.',
			'woocommerce'
		),
	},
	about: {
		label: __( 'about', 'woocommerce' ),
		description: __( 'about', 'woocommerce' ),
	},
	services: {
		label: __( 'services', 'woocommerce' ),
		description: __( 'services', 'woocommerce' ),
	},
	testimonials: {
		label: __( 'testimonials', 'woocommerce' ),
		description: __( 'testimonials', 'woocommerce' ),
	},
};
