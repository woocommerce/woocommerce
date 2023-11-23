/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	aiOnline: {
		heading: __( 'Welcome to your AI-generated store!', 'woocommerce' ),
		description: {
			desktop: __(
				"This is where you can start customizing the look and feel of your store, including adding your logo, and changing colors and layouts. Take a quick tour to discover what's possible.",
				'woocommerce'
			),
		},
	},
	aiOffline: {
		heading: __( 'Welcome to your store!', 'woocommerce' ),
		description: {
			desktop: __(
				"We encountered some issues while generating content with AI. But don't worry — you can still customize the look and feel of your store, including adding your logo, and changing colors and layouts. Take a quick tour to discover what's possible.",
				'woocommerce'
			),
		},
	},
};
