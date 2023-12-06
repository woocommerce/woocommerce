/**
 * External dependencies
 */
import { useContext, useEffect } from '@wordpress/element';
import { CurrencyContext } from '@woocommerce/currency';
import { numberFormat } from '@woocommerce/number';

export const ShippingCurrencyContext = () => {
	const context = useContext( CurrencyContext );

	useEffect( () => {
		window.wc.ShippingCurrencyContext =
			window.wc.ShippingCurrencyContext || context;
		window.wc.ShippingCurrencyNumberFormat =
			window.wc.ShippingCurrencyNumberFormat || numberFormat;
	}, [ context ] );

	return null;
};
