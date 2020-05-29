/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, bill } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import edit from './edit';

const blockConfig = {
	title: __( 'Product Price', 'woo-gutenberg-products-block' ),
	description: __(
		'Display the price of a product.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ bill } />,
		foreground: '#96588a',
	},
	edit,
};

registerBlockType( 'woocommerce/product-price', {
	...sharedConfig,
	...blockConfig,
} );
