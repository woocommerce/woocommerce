/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord, ProductStatus } from '../types';

import { ProductSelector } from '../components/product-selector';

const LINKED_PRODUCT_STATUSES: ProductStatus[] = [ 'publish', 'draft' ];

const fieldDefinition = {
	label: __( 'Cross-sells', 'woocommerce' ),
	description: __(
		"Recommend related or complementary items to encourage additional purchases. These will be shown in the customer's shopping cart.",
		'woocommerce'
	),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	Edit: ( { data, onChange, field } ) => (
		<ProductSelector
			label={ field.label }
			selectedProductIds={
				( data.cross_sell_ids as number[] | undefined ) ?? []
			}
			onSelectedProductIdsChange={ ( ids ) =>
				onChange( { cross_sell_ids: ids } )
			}
			excludeProductIds={ data.id ? [ data.id ] : undefined }
			includeProductStatuses={ LINKED_PRODUCT_STATUSES }
		/>
	),
};
