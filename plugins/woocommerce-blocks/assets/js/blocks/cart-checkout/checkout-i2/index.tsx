/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, card } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import { blockName, blockAttributes } from './attributes';
import './inner-blocks';

const settings = {
	title: __( 'Checkout i2', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ card } />,
		foreground: '#874FB9',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a checkout form so your customers can submit orders.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: false,
	},
	attributes: blockAttributes,
	apiVersion: 2,
	edit: Edit,
	save: Save,
	transforms: {
		to: [
			{
				type: 'block',
				blocks: [ 'woocommerce/checkout' ],
				transform: ( attributes ) => {
					return createBlock( 'woocommerce/checkout', {
						attributes,
					} );
				},
			},
		],
		from: [
			{
				type: 'block',
				blocks: [ 'woocommerce/checkout-i2' ],
				transform: ( attributes ) => {
					return createBlock( 'woocommerce/checkout-i2', attributes );
				},
			},
		],
	},
};

registerFeaturePluginBlockType( blockName, settings );
