/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { BlockAttributes } from './types';
import save from '../save';
import { isTryingToDisplayLegacySaleBadge } from './utils';

// In v2, we're migrating the `showSaleBadge` attribute to an inner block.
const v1 = {
	save,
	attributes: metadata.attributes,
	isEligible: ( { showSaleBadge }: BlockAttributes ) =>
		isTryingToDisplayLegacySaleBadge( showSaleBadge ),
	migrate: ( attributes: BlockAttributes ) => {
		const { showSaleBadge, saleBadgeAlign } = attributes;

		// If showSaleBadge is false, it means that the sale badge was explicitly set to false.
		if ( showSaleBadge === false ) {
			return [ attributes ];
		}
		// Otherwise, it's either:
		// - true explicitly or
		// - undefined (implicit true by default).

		return [
			{
				...attributes,
				showSaleBadge: false,
			},
			[
				createBlock( 'woocommerce/product-sale-badge', {
					align: saleBadgeAlign,
				} ),
			],
		];
	},
};

export default [ v1 ];
