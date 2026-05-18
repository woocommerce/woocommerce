/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { DataFormControlProps, Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { validatePrice, toNumberOrNaN } from '../price/utils';
import { formatCurrency, getCurrencyObject } from '../utils/currency';
import { CurrencyControl } from '../components/currency-input';

import type { ProductEntityRecord } from '../types';

const fieldDefinition = {
	type: 'text',
	label: __( 'Cost of goods', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
	isVisible: ( item: ProductEntityRecord ) =>
		item.cost_of_goods_sold !== undefined,
} satisfies Partial< Field< ProductEntityRecord > >;

function getDefinedCostValue( item: ProductEntityRecord ) {
	return item.cost_of_goods_sold?.values?.[ 0 ]?.defined_value;
}

function CostOfGoodsSoldInput( {
	data,
	field,
	onChange,
	validity,
}: DataFormControlProps< ProductEntityRecord > ) {
	const costOfGoodsSold = data.cost_of_goods_sold ?? {};
	const [ firstValue = {}, ...remainingValues ] =
		costOfGoodsSold.values ?? [];

	return (
		<CurrencyControl
			id={ `currency-input-${ field.id }` }
			label={ field.label }
			value={ getDefinedCostValue( data ) ?? '' }
			onChange={ ( newValue: string ) => {
				onChange( {
					cost_of_goods_sold: {
						...costOfGoodsSold,
						values:
							newValue === ''
								? []
								: [
										{
											...firstValue,
											defined_value: newValue,
										},
										...remainingValues,
								  ],
					},
				} );
			} }
			customValidity={ validity?.custom }
		/>
	);
}

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => getDefinedCostValue( item ),
	getValueFormatted: ( { item } ) => {
		const value = getDefinedCostValue( item );
		const numberValue = toNumberOrNaN( value );

		if (
			value === undefined ||
			value === null ||
			Number.isNaN( numberValue )
		) {
			return '\u2014';
		}

		return formatCurrency( numberValue, getCurrencyObject().code );
	},
	isValid: {
		custom: ( item ) => validatePrice( getDefinedCostValue( item ) ),
	},
	Edit: CostOfGoodsSoldInput,
};
