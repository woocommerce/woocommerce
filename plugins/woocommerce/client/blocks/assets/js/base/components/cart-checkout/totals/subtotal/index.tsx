/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	FormattedMonetaryAmount,
	TotalsItem,
} from '@woocommerce/blocks-components';
import { getSetting } from '@woocommerce/settings';
import { getSubtotalRoundingNote, getTaxLabels } from '@woocommerce/base-utils';
import type {
	CartResponseItem,
	CartResponseTotals,
	Currency,
} from '@woocommerce/types';
import type { ReactElement } from 'react';

/**
 * Internal dependencies
 */
import RoundedTotalInfo from '../../rounded-total-info';

interface TotalsSubtotalProps {
	currency: Currency;
	cartItems: CartResponseItem[];
	cartTotals: CartResponseTotals;
	showSkeleton: boolean;
}

/**
 * The items subtotal, with rounding details when it isn't the sum of the line totals shown.
 */
const TotalsSubtotal = ( {
	currency,
	cartItems,
	cartTotals,
	showSkeleton,
}: TotalsSubtotalProps ): ReactElement => {
	const includeTax = getSetting< boolean >(
		'displayCartPricesIncludingTax',
		false
	);
	const note = getSubtotalRoundingNote(
		cartItems,
		cartTotals,
		includeTax,
		getSetting< boolean >( 'taxRoundAtSubtotal', false ),
		getTaxLabels()
	);
	const subtotal =
		parseInt( cartTotals.total_items, 10 ) +
		( includeTax ? parseInt( cartTotals.total_items_tax, 10 ) : 0 );

	return (
		<TotalsItem
			currency={ currency }
			label={ __( 'Subtotal', 'woocommerce' ) }
			value={
				note ? (
					<RoundedTotalInfo note={ note }>
						<FormattedMonetaryAmount
							currency={ currency }
							value={ subtotal }
						/>
					</RoundedTotalInfo>
				) : (
					subtotal
				)
			}
			showSkeleton={ showSkeleton }
		/>
	);
};

export default TotalsSubtotal;
