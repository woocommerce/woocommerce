/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockVariation } from '@wordpress/blocks';

const variations: BlockVariation[] = [
	{
		name: 'product-filters-overlay-navigation-open-trigger',
		title: __( 'Overlay Navigation (Experimental)', 'woocommerce' ),
		attributes: {
			triggerType: 'open-overlay',
		},
		isDefault: false,
	},
];

export const blockVariations = variations;
