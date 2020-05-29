/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, star } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import edit from './edit';

const blockConfig = {
	title: __( 'Product Rating', 'woo-gutenberg-products-block' ),
	description: __(
		'Display the average rating of a product.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ star } />,
		foreground: '#96588a',
	},
	edit,
};

registerBlockType( 'woocommerce/product-rating', {
	...sharedConfig,
	...blockConfig,
} );
