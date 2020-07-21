/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, cart } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import edit from './edit';

const blockConfig = {
	title: __( 'Add to Cart Button', 'woocommerce' ),
	description: __(
		'Display a call to action button which either adds the product to the cart, or links to the product page.',
		'woocommerce'
	),
	icon: {
		src: <Icon srcElement={ cart } />,
		foreground: '#96588a',
	},
	edit,
};

registerBlockType( 'woocommerce/product-button', {
	...sharedConfig,
	...blockConfig,
} );
