/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InputControl } from '@wordpress/ui';
import type { DataFormControlProps, Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	type: 'integer',
	label: __( 'Stock quantity', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: {
		operators: [
			'is',
			'greaterThan',
			'greaterThanOrEqual',
			'lessThan',
			'lessThanOrEqual',
		],
	},
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	isVisible: ( item ) => {
		return !! item.manage_stock;
	},
	Edit: ( {
		data,
		onChange,
		hideLabelFromVision,
		field,
	}: DataFormControlProps< ProductEntityRecord > ) => {
		const raw = ( data as ProductEntityRecord ).stock_quantity;
		const value =
			typeof raw === 'number'
				? String( raw )
				: typeof raw === 'string'
				? raw
				: '';

		return (
			<InputControl
				label={ hideLabelFromVision ? '' : field.label }
				type="number"
				step={ 1 }
				value={ value }
				onChange={ ( event ) => {
					const next = event.target.value;
					onChange( {
						stock_quantity:
							next === '' ? null : Number( next ),
					} );
				} }
			/>
		);
	},
};
