/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';
import { Icon, cart } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import edit from './edit';
import attributes from './attributes';

const blockConfig = {
	title: __( 'Add to Cart', 'woo-gutenberg-products-block' ),
	description: __(
		'Displays an add to cart button. Optionally displays other add to cart form elements.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ cart } />,
		foreground: '#96588a',
	},
	edit,
	attributes,
};

registerExperimentalBlockType( 'woocommerce/product-add-to-cart', {
	...sharedConfig,
	...blockConfig,
} );
