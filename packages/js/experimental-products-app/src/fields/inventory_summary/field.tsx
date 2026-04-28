/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
	elements: [
		{ label: __( 'In stock', 'woocommerce' ), value: 'instock' },
		{ label: __( 'Out of stock', 'woocommerce' ), value: 'outofstock' },
		{ label: __( 'On backorder', 'woocommerce' ), value: 'onbackorder' },
	],
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	render: ( { item, field } ) => {
		const status = item.stock_status?.toLowerCase?.() ?? 'instock';
		const match = field.elements?.find(
			( element: { value: string } ) => element.value === status
		);

		if ( item.manage_stock ) {
			if ( ! Number.isFinite( item.stock_quantity ) ) {
				return (
					<div className="woocommerce-fields-field__inventory-summary">
						{ __( 'No stock quantity set', 'woocommerce' ) }
					</div>
				);
			}

			return (
				<div className="woocommerce-fields-field__inventory-summary">
					{ sprintf(
						/* translators: %d: stock quantity */
						__( '%d available in stock', 'woocommerce' ),
						item.stock_quantity
					) }
				</div>
			);
		}
		return (
			<div className="woocommerce-fields-field__inventory-summary">
				{ match?.label ?? item.stock_status }
			</div>
		);
	},
};
