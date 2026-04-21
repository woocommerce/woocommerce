/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

function isValidStockStatus( value: string ) {
	return (
		value === 'instock' || value === 'outofstock' || value === 'onbackorder'
	);
}

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
		return match ? (
			<div className="woocommerce-fields-field__stock">
				<span
					className={ `woocommerce-fields-field__stock-label woocommerce-fields-field__stock-label--${ match.value }` }
				>
					{ match.label }
				</span>
				{ item.stock_quantity && item.stock_quantity > 0 && (
					<span className="woocommerce-fields-field__stock-quantity">
						({ item.stock_quantity })
					</span>
				) }
			</div>
		) : (
			item.stock_status
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
