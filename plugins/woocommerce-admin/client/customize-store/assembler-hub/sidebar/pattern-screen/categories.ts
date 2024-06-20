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
	featured_selling: {
		label: __( 'Featured selling', 'woocommerce' ),
		description: __(
			'Put the spotlight on one or more of your products or product categories.',
			'woocommerce'
		),
	},
	about: {
		label: __( 'about', 'woocommerce' ),
		description: __(
			'Show your shoppers what’s special about your business.',
			'woocommerce'
		),
	},
	services: {
		label: __( 'services', 'woocommerce' ),
		description: __( '', 'woocommerce' ),
	},
	reviews: {
		label: __( 'Reviews', 'woocommerce' ),
		description: __(
			'Encourage sales by sharing positive feedback from happy shoppers.',
			'woocommerce'
		),
	},
	social_media: {
		label: __( 'Social media', 'woocommerce' ),
		description: __(
			'Promote your social channels and give shoppers a way to see your latest products and news.',
			'woocommerce'
		),
	},
	// newsletter: {
	// 	label: __( 'Newsletter', 'woocommerce' ),
	// 	description: __(
	// 		'Keep in touch with your shoppers with a newsletter signup form.',
	// 		'woocommerce'
	// 	),
	// },
};
