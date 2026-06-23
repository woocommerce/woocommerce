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
	label: __( 'Upsells', 'woocommerce' ),
	description: __(
		'Suggest higher-value or premium versions of this item to encourage upgrades. These will be shown on the same page as this product.',
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
				( data.upsell_ids as number[] | undefined ) ?? []
			}
			onSelectedProductIdsChange={ ( ids ) =>
				onChange( { upsell_ids: ids } )
			}
			excludeProductIds={ data.id ? [ data.id ] : undefined }
			includeProductStatuses={ LINKED_PRODUCT_STATUSES }
		/>
	),
};
