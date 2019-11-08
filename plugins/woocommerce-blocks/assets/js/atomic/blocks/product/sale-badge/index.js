/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { ProductSaleBadge } from '@woocommerce/atomic-components/product';
import { IconProductOnSale } from '@woocommerce/block-components/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';

const blockConfig = {
	title: __( 'On-Sale Badge', 'woo-gutenberg-products-block' ),
	description: __(
		'Displays an on-sale badge if the product is on-sale.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <IconProductOnSale />,
		foreground: '#96588a',
	},
	supports: {
		html: false,
	},
	edit( props ) {
		const { align, product } = props.attributes;

		return <ProductSaleBadge product={ product } align={ align } />;
	},
};

registerBlockType( 'woocommerce/product-sale-badge', {
	...sharedConfig,
	...blockConfig,
} );
