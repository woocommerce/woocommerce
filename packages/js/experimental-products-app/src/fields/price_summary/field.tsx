/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { getCurrencyObject } from '../utils/currency';

import type { ProductEntityRecord } from '../types';

import { toNumberOrNaN } from '../price/utils';

const formatPrice = ( amount: number, currencyCode: string ) => {
	const locale = document.documentElement.lang || 'en-US';
	return new Intl.NumberFormat( locale, {
		style: 'currency',
		currency: currencyCode,
	} ).format( amount );
};

const fieldDefinition = {
	enableSorting: false,
	enableHiding: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	render: ( { item } ) => {
		const salePrice = toNumberOrNaN( item.sale_price );
		const regularPrice = toNumberOrNaN( item.regular_price );
		const hasSalePrice =
			Boolean( item.on_sale ) && Number.isFinite( salePrice );

		const priceToDisplay = hasSalePrice ? salePrice : regularPrice;

		if ( ! Number.isFinite( priceToDisplay ) ) {
			return null;
		}

		const currency = getCurrencyObject();
		const priceText = formatPrice( priceToDisplay, currency.code );

		if ( hasSalePrice ) {
			return (
				<span>
					{ priceText }
					{ ' · ' }
					{ __( 'On sale', 'woocommerce' ) }
				</span>
			);
		}

		return <span>{ priceText }</span>;
	},
};
