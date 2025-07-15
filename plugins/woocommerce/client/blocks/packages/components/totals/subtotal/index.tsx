/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import type { Currency } from '@woocommerce/types';
import type { ReactElement } from 'react';

/**
 * Internal dependencies
 */
import TotalsItem from '../item';

interface Values {
	total_items: string;
	total_items_tax: string;
}

export interface SubtotalProps {
	className?: string;
	currency: Currency;
	values: Values | Record< string, never >;
	showSkeleton?: boolean;
}

const Subtotal = ( {
	currency,
	values,
	className,
	showSkeleton,
}: SubtotalProps ): ReactElement => {
	const { total_items: totalItems, total_items_tax: totalItemsTax } = values;
	const itemsValue = parseInt( totalItems, 10 );
	const itemsTaxValue = parseInt( totalItemsTax, 10 );

	return (
		<TotalsItem
			className={ className }
			currency={ currency }
			label={ __( 'Subtotal', 'woocommerce' ) }
			value={
				getSetting( 'displayCartPricesIncludingTax', false )
					? itemsValue + itemsTaxValue
					: itemsValue
			}
			showSkeleton={ showSkeleton }
		/>
	);
};

export default Subtotal;
