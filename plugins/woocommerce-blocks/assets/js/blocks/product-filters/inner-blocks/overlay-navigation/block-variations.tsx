/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockVariation } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';

const variations: BlockVariation[] = [
	{
		name: 'product-filters-overlay-navigation-open-trigger',
		title: __( 'Overlay Navigation (Experimental)', 'woocommerce' ),
		attributes: {
			triggerType: 'open-overlay',
		},
		isDefault: false,
		icon: <Icon icon={ button } />,
		isActive: [ 'triggerType' ],
	},
];

export const blockVariations = variations;
