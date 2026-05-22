/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';
import type { Field } from '@wordpress/dataviews';
import { InputControl, Stack, InputLayout } from '@wordpress/ui';

/**
 * Internal dependencies
 */

import { formatCurrency, getCurrencyObject } from '../utils/currency';

import type { ProductEntityRecord } from '../types';

import { toNumberOrNaN } from './utils';

type PriceRange = [ number | string, number | string ];
type PriceFilterData = Omit< ProductEntityRecord, 'price' > & {
	price: string | PriceRange;
};

const isLeftPositioned = ( position: string ) =>
	position === 'left' || position === 'left_space';

const isRightPositioned = ( position: string ) =>
	position === 'right' || position === 'right_space';

const CurrencyPrefixSlot = () => {
	const currency = getCurrencyObject();

	if ( ! isLeftPositioned( currency.symbolPosition ) ) {
		return null;
	}

	return (
		<InputLayout.Slot padding="minimal">
			{ currency.symbol }
		</InputLayout.Slot>
	);
};

const CurrencySuffixSlot = () => {
	const currency = getCurrencyObject();

	if ( ! isRightPositioned( currency.symbolPosition ) ) {
		return null;
	}

	return (
		<InputLayout.Slot padding="minimal">
			{ currency.symbol }
		</InputLayout.Slot>
	);
};

const fieldDefinition = {
	label: __( 'Price', 'woocommerce' ),
	enableSorting: true,
	filterBy: {
		operators: [ 'is', 'between', 'greaterThanOrEqual', 'lessThanOrEqual' ],
	},
} satisfies Partial< Field< PriceFilterData > >;

export const fieldExtensions: Partial< Field< PriceFilterData > > = {
	...fieldDefinition,
	getValueFormatted: ( { item, field } ) => {
		const value: unknown = field.getValue( { item } );
		if ( value === null || value === undefined || value === '' ) {
			// Return em-dash for empty values to prevent DataViews fallback to "undefined"
			return '\u2014';
		}
		const num = toNumberOrNaN( value );
		if ( Number.isNaN( num ) ) {
			// Return em-dash for NaN values to prevent DataViews fallback to "NaN"
			return '\u2014';
		}
		const currency = getCurrencyObject();
		return formatCurrency( num, currency.code );
	},
	render: ( { item } ) => {
		const rawPrice = item.price;
		const price = toNumberOrNaN( rawPrice );

		// Show an em dash when no sale price is provided instead of leaving the cell blank.
		if (
			rawPrice === undefined ||
			rawPrice === null ||
			rawPrice === '' ||
			Number.isNaN( price )
		) {
			return <span>{ '\u2014' }</span>;
		}

		const currency = getCurrencyObject();
		const regularPrice = toNumberOrNaN( item.regular_price );

		// Only render the strikethrough when the regular price is a valid number,
		// since partially saved products may send empty or NaN-like values here.
		if ( item.on_sale && Number.isFinite( regularPrice ) ) {
			return (
				<Stack direction="row">
					<s>{ formatCurrency( regularPrice, currency.code ) }</s>
					<span>{ formatCurrency( price, currency.code ) }</span>
				</Stack>
			);
		}

		return <span>{ formatCurrency( price, currency.code ) }</span>;
	},
	Edit: ( { data, onChange, hideLabelFromVision, operator, field } ) => {
		const currency = getCurrencyObject();
		const step = Math.pow( 10, -currency.precision );
		const [ minValue = '', maxValue = '' ] = Array.isArray( data.price )
			? data.price
			: [];
		const min = String( minValue );
		const max = String( maxValue );

		const onChangeMin = useCallback(
			( newValue: string | undefined ) => {
				onChange( {
					price: [ parseFloat( newValue || '' ), max ] as [
						number | string,
						number | string
					],
				} );
			},
			[ onChange, max ]
		);

		const onChangeMax = useCallback(
			( newValue: string | undefined ) => {
				onChange( {
					price: [ min, parseFloat( newValue || '' ) ] as [
						number | string,
						number | string
					],
				} );
			},
			[ onChange, min ]
		);

		if ( operator === 'between' ) {
			return (
				<Stack direction="row">
					<InputControl
						label={ __( 'From', 'woocommerce' ) }
						type="number"
						step={ step }
						value={ min }
						prefix={ <CurrencyPrefixSlot /> }
						suffix={ <CurrencySuffixSlot /> }
						onChange={ ( event ) =>
							onChangeMin( event.target.value )
						}
					/>
					<InputControl
						label={ __( 'To', 'woocommerce' ) }
						type="number"
						step={ step }
						value={ max }
						prefix={ <CurrencyPrefixSlot /> }
						suffix={ <CurrencySuffixSlot /> }
						onChange={ ( event ) =>
							onChangeMax( event.target.value )
						}
					/>
				</Stack>
			);
		}

		const singleValue = typeof data.price === 'string' ? data.price : '';

		return (
			<InputControl
				label={ hideLabelFromVision ? '' : field.label }
				type="number"
				step={ step }
				value={ singleValue }
				prefix={ <CurrencyPrefixSlot /> }
				suffix={ <CurrencySuffixSlot /> }
				onChange={ ( event ) =>
					onChange( { price: event.target.value } )
				}
			/>
		);
	},
};
