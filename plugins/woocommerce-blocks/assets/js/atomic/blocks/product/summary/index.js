/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, notes } from '@woocommerce/icons';
import { ProductSummary } from '@woocommerce/atomic-components/product';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';

const blockConfig = {
	title: __( 'Product Summary', 'woo-gutenberg-products-block' ),
	description: __(
		'Display a short description about a product.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ notes } />,
		foreground: '#96588a',
	},

	/**
	 * Renders the edit view for a block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		const { attributes } = props;

		return <ProductSummary product={ attributes.product } />;
	},
};

registerBlockType( 'woocommerce/product-summary', {
	...sharedConfig,
	...blockConfig,
} );
