/**
 * External dependencies
 */
import { useContext, useMemo } from '@wordpress/element';
import { CurrencyContext } from '@woocommerce/currency';

export type AmountFormatter = ( amount: string, currency: string ) => string;

/**
 * Format a decimal amount string in a currency. The store currency uses the WooCommerce
 * formatting settings; any other currency uses the browser locale.
 *
 * @param amount                  Decimal amount in major units.
 * @param currency                ISO 4217 currency code.
 * @param store                   The store currency instance from CurrencyContext.
 * @param store.getCurrencyConfig Returns the store currency config.
 * @param store.formatAmount      Formats an amount in the store currency.
 */
export const formatAmount = (
	amount: string,
	currency: string,
	store: {
		getCurrencyConfig: () => { code: string };
		formatAmount: ( amount: number | string ) => string;
	}
): string => {
	const value = Number( amount );
	const fallback = `${ amount } ${ currency }`;

	if ( ! Number.isFinite( value ) ) {
		return fallback;
	}

	if ( currency === store.getCurrencyConfig().code ) {
		return store.formatAmount( value );
	}

	try {
		return new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency,
		} ).format( value );
	} catch {
		return fallback;
	}
};

export function useAmountFormatter(): AmountFormatter {
	const store = useContext( CurrencyContext );

	return useMemo(
		() => ( amount: string, currency: string ) =>
			formatAmount( amount, currency, store ),
		[ store ]
	);
}
