/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Disabled } from '@wordpress/components';
import { Icon, cart } from '@woocommerce/icons';
import { ProductButton } from '@woocommerce/atomic-components/product';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';

const blockConfig = {
	title: __( 'Add to Cart Button', 'woo-gutenberg-products-block' ),
	description: __(
		'Display a call to action button which either adds the product to the cart, or links to the product page.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ cart } />,
		foreground: '#96588a',
	},
	edit( props ) {
		const { attributes } = props;

		return (
			<Disabled>
				<ProductButton product={ attributes.product } />
			</Disabled>
		);
	},
};

registerBlockType( 'woocommerce/product-button', {
	...sharedConfig,
	...blockConfig,
} );
