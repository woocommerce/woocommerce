/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import { ProductPrice } from '@woocommerce/atomic-components/product';
import sharedConfig from '../shared-config';

const blockConfig = {
	title: __( 'Product Price', 'woo-gutenberg-products-block' ),
	description: __(
		'Display the price of a product.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Gridicon icon="money" />,
		foreground: '#96588a',
	},
	edit( props ) {
		const { attributes } = props;

		return <ProductPrice product={ attributes.product } />;
	},
};

registerBlockType( 'woocommerce/product-price', {
	...sharedConfig,
	...blockConfig,
} );
