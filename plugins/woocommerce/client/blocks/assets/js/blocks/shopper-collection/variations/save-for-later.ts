/**
 * External dependencies
 */
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { starEmpty } from '@wordpress/icons';

export const VARIATION_NAME = 'save-for-later';

registerBlockVariation( 'woocommerce/shopper-collection', {
	name: VARIATION_NAME,
	title: __( 'Save for Later', 'woocommerce' ),
	description: __(
		'Display items the shopper has saved from their cart for later.',
		'woocommerce'
	),
	icon: starEmpty,
	scope: [ 'inserter', 'block' ],
	attributes: {
		listName: 'save-for-later',
		showQuantity: true,
		showVariation: true,
		showSavedDate: false,
	},
	isActive: [ 'listName' ],
} );
