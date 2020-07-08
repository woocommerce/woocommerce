/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, barcode } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import edit from './edit';

const blockConfig = {
	title: __( 'Product SKU', 'woocommerce' ),
	description: __(
		'Display the SKU of a product.',
		'woocommerce'
	),
	icon: {
		src: <Icon srcElement={ barcode } />,
		foreground: '#96588a',
	},
	edit,
};

registerBlockType( 'woocommerce/product-sku', {
	...sharedConfig,
	...blockConfig,
} );
