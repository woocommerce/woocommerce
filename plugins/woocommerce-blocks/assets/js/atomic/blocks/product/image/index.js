/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, image } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import attributes from './attributes';
import edit from './edit';

const blockConfig = {
	title: __( 'Product Image', 'woo-gutenberg-products-block' ),
	description: __(
		'Display the main product image',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ image } />,
		foreground: '#96588a',
	},
	attributes,
	edit,
};

registerBlockType( 'woocommerce/product-image', {
	...sharedConfig,
	...blockConfig,
} );
