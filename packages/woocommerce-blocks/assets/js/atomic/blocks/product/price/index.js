/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { CURRENCY } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import attributes from './attributes';
import edit from './edit';

const blockConfig = {
	title: __( 'Product Price', 'woocommerce' ),
	description: __(
		'Display the price of a product.',
		'woocommerce'
	),
	icon: {
		src: <b style={ { color: '$96588a' } }>{ CURRENCY.symbol }</b>,
		foreground: '#96588a',
	},
	edit,
	attributes,
};

registerBlockType( 'woocommerce/product-price', {
	...sharedConfig,
	...blockConfig,
} );
