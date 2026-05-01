/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { VariantCell, ValuesCell, PriceCell, StockCell } from './cells';
import type { Variation } from '../types';

export const variationFields: Field< Variation >[] = [
	{
		id: 'variant',
		label: __( 'Variant', 'woocommerce' ),
		getValue: ( { item } ) => String( item.id ),
		render: ( { item } ) => <VariantCell item={ item } />,
		enableSorting: false,
		enableHiding: false,
	},
	{
		id: 'values',
		label: __( 'Values', 'woocommerce' ),
		getValue: ( { item } ) =>
			item.attributes.map( ( a ) => a.option ).join( ' ' ),
		render: ( { item } ) => <ValuesCell item={ item } />,
		enableSorting: false,
		enableHiding: false,
		enableGlobalSearch: true,
	},
	{
		id: 'price',
		label: __( 'Price', 'woocommerce' ),
		getValue: ( { item } ) => item.regular_price,
		render: ( { item } ) => <PriceCell item={ item } />,
		enableSorting: false,
	},
	{
		id: 'stock',
		label: __( 'Stock', 'woocommerce' ),
		getValue: ( { item } ) => item.stock_status,
		render: ( { item } ) => <StockCell item={ item } />,
		enableSorting: false,
	},
];
