/**
 * External dependencies
 */
import { normalizeCurrencyResponse } from '@woocommerce/price-format/utils/currency';
import type {
	CartItemPrices,
	CartItemTotals,
	Currency,
	CurrencyInfo,
} from '@woocommerce/types';

/**
 * Why a cart line's total isn't the unit price shown × quantity. No translations or settings, so the Interactivity API
 * Mini-Cart (a script module) can use it too; rounded-total.ts words it for the React blocks.
 */

export type LineTotals = Pick<
	CartItemTotals,
	'line_subtotal' | 'line_subtotal_tax'
>;

export interface RoundedTotalLineItem {
	prices: Pick< CartItemPrices, 'price' | 'raw_prices' > & CurrencyInfo;
	totals: LineTotals & CurrencyInfo;
	quantity: number;
}

export interface LineRounding {
	/** The line total shown, in the minor units of `currency`. */
	line: number;
	/** The line's currency. */
	currency: Currency;
	/** Whether the line has tax, so prices get an "(incl. VAT)" or "(excl. VAT)" suffix. */
	hasTax: boolean;
	/**
	 * The exact unit price that explains the line: exact × quantity, rounded, is the line total. Null when only adding
	 * or taking off tax explains it.
	 */
	units: null | {
		quantity: number;
		/** Exact unit price, in minor units of `exactMinorUnit` decimals. */
		exact: number;
		exactMinorUnit: number;
		/** The unit price the cart shows, when it isn't the exact price; in minor units of `shownMinorUnit`. */
		shown: number | null;
		shownMinorUnit: number;
	};
}

/**
 * Most decimals an exact price is shown with.
 */
const MAX_EXACT_DECIMALS = 6;

const toInt = ( value: string | number | undefined ): number => {
	const parsed = parseInt( String( value ?? 0 ), 10 );
	return Number.isFinite( parsed ) ? parsed : 0;
};

/**
 * The line total shown for a cart item, in the currency's minor units.
 */
export const getShownLineTotal = (
	totals: LineTotals,
	includeTax: boolean
): number =>
	toInt( totals.line_subtotal ) +
	( includeTax ? toInt( totals.line_subtotal_tax ) : 0 );

/**
 * Work out why a cart line's total isn't the unit price shown × quantity. Returns null when it adds up, or when no
 * simple explanation fits.
 *
 * @param item          Store API cart item.
 * @param includeTax    Whether the cart shows prices including tax.
 * @param storeCurrency The store's currency, for prices without their own currency data.
 */
export const getLineRounding = (
	item: RoundedTotalLineItem,
	includeTax: boolean,
	storeCurrency: Currency
): LineRounding | null => {
	const { prices, totals, quantity } = item;
	const unit = parseInt( prices.price, 10 );
	const raw = parseInt( prices.raw_prices?.price, 10 );
	const rawPrecision = toInt( prices.raw_prices?.precision );

	if (
		! quantity ||
		! Number.isFinite( unit ) ||
		! Number.isFinite( raw ) ||
		! rawPrecision
	) {
		return null;
	}

	// Work at one precision, so a unit price with more decimals than the line total (e.g. £0.207) compares exactly.
	const currency = normalizeCurrencyResponse( totals, storeCurrency );
	const priceCurrency = normalizeCurrencyResponse( prices, currency );
	const precision = Math.max(
		priceCurrency.minorUnit,
		currency.minorUnit,
		rawPrecision
	);
	const line = getShownLineTotal( totals, includeTax );
	const unitAt = unit * 10 ** ( precision - priceCurrency.minorUnit );

	if (
		unitAt * quantity ===
		line * 10 ** ( precision - currency.minorUnit )
	) {
		return null;
	}

	const hasTax = toInt( totals.line_subtotal_tax ) > 0;

	// The exact price, trimmed to at most 6 decimals, half up.
	let exactMinorUnit = Math.min( rawPrecision, MAX_EXACT_DECIMALS );
	const step = 10 ** ( rawPrecision - exactMinorUnit );
	let exact = Math.floor( raw / step );
	if ( raw - exact * step >= step / 2 ) {
		exact++;
	}

	// The exact line total, rounded half up to the currency's minor unit.
	const lineStep = 10 ** ( exactMinorUnit - currency.minorUnit );
	const exactLine = exact * quantity;
	let roundedLine = Math.floor( exactLine / lineStep );
	if ( exactLine - roundedLine * lineStep >= lineStep / 2 ) {
		roundedLine++;
	}

	if ( roundedLine !== line ) {
		return hasTax ? { line, currency, hasTax, units: null } : null;
	}

	// Show the exact price with the decimals it needs, but at least the currency's.
	while ( exactMinorUnit > currency.minorUnit && exact % 10 === 0 ) {
		exact /= 10;
		exactMinorUnit--;
	}

	return {
		line,
		currency,
		hasTax,
		units: {
			quantity,
			exact,
			exactMinorUnit,
			shown:
				exact * 10 ** ( precision - exactMinorUnit ) === unitAt
					? null
					: unit,
			shownMinorUnit: priceCurrency.minorUnit,
		},
	};
};
