/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	render: ( { item } ) => {
		const upsellCount = item.upsell_ids?.length || 0;
		const crossSellCount = item.cross_sell_ids?.length || 0;
		const total = upsellCount + crossSellCount;
		if ( total === 0 ) {
			return null;
		}
		return <span>{ total }</span>;
	},
};
