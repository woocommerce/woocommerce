/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockVariation } from '@wordpress/blocks';

const variations: BlockVariation[] = [
	{
		name: 'product-filters-overlay-navigation-open-trigger',
		title: __(
			'Product Filters Overlay Navigation (Experimental)',
			'woocommerce'
		),
		attributes: {
			triggerType: 'open-overlay',
		},
		isDefault: false,
	},
];

/**
 * Add `isActive` function to all Product Filters Overlay Navigation block variations.
 * `isActive` function is used to find a variation match from a created
 *  Block by providing its attributes.
 */
variations.forEach( ( variation ) => {
	// @ts-expect-error: `isActive` is currently typed wrong in `@wordpress/blocks`.
	variation.isActive = [ 'triggerType' ];
} );

export const blockVariations = variations;
