/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InputControl, Stack } from '@wordpress/ui';
import type { DataFormControlProps, Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

type StockQuantityRange = [ number | string, number | string ];
type StockQuantityFilterRecord = Omit<
	ProductEntityRecord,
	'stock_quantity'
> & {
	stock_quantity?: number | null | StockQuantityRange;
};

const castValueToString = (
	value: number | string | null | StockQuantityRange | undefined
): string => {
	if ( typeof value === 'number' ) {
		return String( value );
	} else if ( typeof value === 'string' ) {
		return value;
	}
	return '';
};

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
			'between',
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
		operator,
		field,
	}: DataFormControlProps< ProductEntityRecord > ) => {
		const onChangeBetween = onChange as (
			data: Partial< StockQuantityFilterRecord >
		) => void;
		const raw = ( data as StockQuantityFilterRecord ).stock_quantity;

		if ( operator === 'between' ) {
			const [ minRaw = '', maxRaw = '' ] = Array.isArray( raw )
				? raw
				: [];
			const min = String( minRaw );
			const max = String( maxRaw );

			return (
				<Stack direction="row">
					<InputControl
						label={ __( 'From', 'woocommerce' ) }
						type="number"
						step={ 1 }
						value={ min }
						onChange={ ( event ) => {
							const next = event.target.value;
							const nextMin = next === '' ? '' : Number( next );
							onChangeBetween( {
								stock_quantity: [ nextMin, max ],
							} );
						} }
					/>
					<InputControl
						label={ __( 'To', 'woocommerce' ) }
						type="number"
						step={ 1 }
						value={ max }
						onChange={ ( event ) => {
							const next = event.target.value;
							const nextMax = next === '' ? '' : Number( next );
							onChangeBetween( {
								stock_quantity: [ min, nextMax ],
							} );
						} }
					/>
				</Stack>
			);
		}

		const value = castValueToString( raw );
		return (
			<InputControl
				label={ hideLabelFromVision ? '' : field.label }
				type="number"
				step={ 1 }
				value={ value }
				onChange={ ( event ) => {
					const next = event.target.value;
					onChange( {
						stock_quantity: next === '' ? null : Number( next ),
					} );
				} }
			/>
		);
	},
};
