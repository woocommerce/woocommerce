/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { ProductSaleBadge } from '@woocommerce/atomic-components/product';
import { Icon, tag } from '@woocommerce/icons';

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
		src: <Icon srcElement={ tag } />,
		foreground: '#96588a',
	},
	supports: {
		html: false,
	},

	/**
	 * Renders the edit view for a block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		const { align, product } = props.attributes;

		return <ProductSaleBadge product={ product } align={ align } />;
	},
};

registerBlockType( 'woocommerce/product-sale-badge', {
	...sharedConfig,
	...blockConfig,
} );
