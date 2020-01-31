/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, star } from '@woocommerce/icons';
import { ProductRating } from '@woocommerce/atomic-components/product';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';

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

	/**
	 * Renders the edit view for a block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		const { attributes } = props;

		return <ProductRating product={ attributes.product } />;
	},
};

registerBlockType( 'woocommerce/product-rating', {
	...sharedConfig,
	...blockConfig,
} );
