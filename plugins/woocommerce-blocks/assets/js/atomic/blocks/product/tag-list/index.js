/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, tag } from '@woocommerce/icons';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';
import edit from './edit';

const blockConfig = {
	title: __( 'Product Tag List', 'woo-gutenberg-products-block' ),
	description: __(
		'Display a list of tags belonging to a product.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ tag } />,
		foreground: '#96588a',
	},
	edit,
};

registerExperimentalBlockType( 'woocommerce/product-tag-list', {
	...sharedConfig,
	...blockConfig,
} );
