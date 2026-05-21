/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { ProductStatusBadge } from '../components/product-status-badge';

const fieldDefinition = {
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	render: ( { item } ) => (
		<div className="woocommerce-fields-field__visibility-summary">
			<ProductStatusBadge
				status={ item.status as ProductEntityRecord[ 'status' ] }
			/>
		</div>
	),
};
