/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import attributes from './attributes';
import edit from './edit';

const blockConfig = {
	title: __( 'Product Title', 'woocommerce' ),
	description: __(
		'Display the name of a product.',
		'woocommerce'
	),
	icon: {
		src: 'heading',
		foreground: '#96588a',
	},
	attributes,
	edit,
};

registerBlockType( 'woocommerce/product-title', {
	...sharedConfig,
	...blockConfig,
} );
