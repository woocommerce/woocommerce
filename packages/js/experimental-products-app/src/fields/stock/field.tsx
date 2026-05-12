/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { Badge } from '@wordpress/ui';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

type StockStatus = 'instock' | 'outofstock' | 'onbackorder';

function isValidStockStatus( value: string ): value is StockStatus {
	return (
		value === 'instock' || value === 'outofstock' || value === 'onbackorder'
	);
}

const stockStatusBadgeIntent: Record<
	StockStatus,
	React.ComponentProps< typeof Badge >[ 'intent' ]
> = {
	instock: 'none',
	outofstock: 'high',
	onbackorder: 'draft',
};

const fieldDefinition = {
	label: __( 'Stock', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: {
		operators: [ 'is' ],
	},
	elements: [
		{ label: __( 'In stock', 'woocommerce' ), value: 'instock' },
		{ label: __( 'Out of stock', 'woocommerce' ), value: 'outofstock' },
		{ label: __( 'On backorder', 'woocommerce' ), value: 'onbackorder' },
	],
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	isVisible: ( item ) => {
		return ! item.manage_stock;
	},
	getValue: ( { item } ) => item.stock_status,
	render: ( { item, field } ) => {
		const match = field?.elements?.find(
			( status ) => status.value === item.stock_status
		);

		if ( ! match || ! isValidStockStatus( match.value ) ) {
			return item.stock_status;
		}

		return (
			<div className="woocommerce-fields-field__stock">
				<Badge intent={ stockStatusBadgeIntent[ match.value ] }>
					{ match.label }
				</Badge>
				{ item.stock_quantity && item.stock_quantity > 0 && (
					<span className="woocommerce-fields-field__stock-quantity">
						({ item.stock_quantity })
					</span>
				) }
			</div>
		);
	},
	Edit: ( { data, onChange, field } ) => (
		<SelectControl
			label={ __( 'Status', 'woocommerce' ) }
			value={ data.stock_status }
			options={ field?.elements || [] }
			onChange={ ( value ) => {
				if ( value && isValidStockStatus( value ) ) {
					onChange( {
						stock_status: value,
					} );
				}
			} }
		/>
	),
};
