/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	formatPrice,
	getCurrencyFromPriceResponse,
} from '@woocommerce/price-format';
import { formatPriceWithCurrency } from '@woocommerce/price-format/utils/currency';
import { getSetting, SITE_CURRENCY } from '@woocommerce/settings';
import type { CartResponseTotals, CurrencyResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	getLineRounding,
	getShownLineTotal,
	type LineTotals,
	type RoundedTotalLineItem,
} from './rounded-total-calculation';

export type { RoundedTotalLineItem } from './rounded-total-calculation';

/**
 * How the store names tax: "VAT" or "Tax", and the price suffixes "(incl. VAT)" and "(excl. VAT)".
 */
export interface TaxLabels {
	tax: string;
	included: string;
	excluded: string;
}

/**
 * Get the store's tax labels from the block settings.
 */
export const getTaxLabels = (): TaxLabels =>
	getSetting< TaxLabels >( 'taxLabels', {
		tax: __( 'Tax', 'woocommerce' ),
		included: __( '(incl. tax)', 'woocommerce' ),
		excluded: __( '(excl. tax)', 'woocommerce' ),
	} );

/**
 * Get the note for a cart line whose total isn't the unit price shown × quantity, e.g. "Rounded to £5.98 from 3 units
 * priced £1.992 (incl. VAT), shown as £1.99 in your cart." Returns null when the line adds up, or when no simple
 * explanation fits.
 *
 * @param item       Store API cart item.
 * @param includeTax Whether the cart shows prices including tax.
 * @param taxLabels  How the store names tax.
 */
export const getLineRoundingNote = (
	item: RoundedTotalLineItem,
	includeTax: boolean,
	taxLabels: TaxLabels
): string | null => {
	const rounding = getLineRounding( item, includeTax, SITE_CURRENCY );

	if ( ! rounding ) {
		return null;
	}

	const { line, currency, hasTax, units } = rounding;
	const lineText = formatPrice( line, currency );

	if ( ! units ) {
		return includeTax
			? sprintf(
					/* translators: 1: line total, e.g. £0.74. 2: "VAT" or "Tax". */
					__(
						'Rounded to %1$s after adding %2$s to the line.',
						'woocommerce'
					),
					lineText,
					taxLabels.tax
				)
			: sprintf(
					/* translators: 1: line total, e.g. £0.62. 2: "VAT" or "Tax". */
					__(
						'Rounded to %1$s after taking %2$s off the line.',
						'woocommerce'
					),
					lineText,
					taxLabels.tax
				);
	}

	let exactText = formatPriceWithCurrency( units.exact, currency, {
		minorUnit: units.exactMinorUnit,
	} );
	if ( hasTax ) {
		exactText += ` ${
			includeTax ? taxLabels.included : taxLabels.excluded
		}`;
	}

	if ( units.shown === null ) {
		return sprintf(
			/* translators: 1: line total, e.g. £0.41. 2: quantity. 3: exact unit price as the cart shows it, e.g. £0.207. */
			_n(
				'Rounded to %1$s from %2$s unit priced %3$s.',
				'Rounded to %1$s from %2$s units priced %3$s.',
				units.quantity,
				'woocommerce'
			),
			lineText,
			String( units.quantity ),
			exactText
		);
	}

	return sprintf(
		/* translators: 1: line total, e.g. £5.98. 2: quantity. 3: exact unit price, e.g. "£1.992 (incl. VAT)". 4: unit price the cart shows, e.g. £1.99. */
		_n(
			'Rounded to %1$s from %2$s unit priced %3$s, shown as %4$s in your cart.',
			'Rounded to %1$s from %2$s units priced %3$s, shown as %4$s in your cart.',
			units.quantity,
			'woocommerce'
		),
		lineText,
		String( units.quantity ),
		exactText,
		formatPriceWithCurrency( units.shown, currency, {
			minorUnit: units.shownMinorUnit,
		} )
	);
};

/**
 * Get the note for an items subtotal that isn't the sum of the line totals shown. Only tax rounded at subtotal level
 * explains that simply, so returns null otherwise, or when the subtotal adds up.
 *
 * @param items              Store API cart items.
 * @param cartTotals         Store API cart totals.
 * @param includeTax         Whether the cart shows prices including tax.
 * @param roundTaxAtSubtotal Whether the store rounds tax at subtotal level rather than per line.
 * @param taxLabels          How the store names tax.
 */
export const getSubtotalRoundingNote = (
	items: Array< { totals: LineTotals } >,
	cartTotals: Pick< CartResponseTotals, 'total_items' | 'total_items_tax' > &
		CurrencyResponse,
	includeTax: boolean,
	roundTaxAtSubtotal: boolean,
	taxLabels: TaxLabels
): string | null => {
	const itemsTax = parseInt( cartTotals.total_items_tax, 10 ) || 0;

	if ( ! includeTax || ! roundTaxAtSubtotal || itemsTax <= 0 ) {
		return null;
	}

	const linesTotal = items.reduce(
		( sum, { totals } ) => sum + getShownLineTotal( totals, includeTax ),
		0
	);
	const subtotal = ( parseInt( cartTotals.total_items, 10 ) || 0 ) + itemsTax;

	return linesTotal === subtotal
		? null
		: sprintf(
				/* translators: 1: subtotal, e.g. £11.95. 2: "VAT" or "Tax". */
				__(
					'Rounded to %1$s after adding %2$s to the subtotal.',
					'woocommerce'
				),
				formatPrice(
					subtotal,
					getCurrencyFromPriceResponse( cartTotals )
				),
				taxLabels.tax
			);
};
