/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, grid } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import save from './save';

/**
 * Holds default config for this collection of blocks.
 */
export default {
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	icon: {
		src: <Icon srcElement={ grid } />,
		foreground: '#96588a',
	},
	supports: {
		html: false,
	},
	parent: [ 'woocommerce/all-products', 'woocommerce/single-product' ],
	save,
	deprecated: [
		{
			save() {},
		},
	],
};
