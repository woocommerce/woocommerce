/**
 * External dependencies
 */
import { useContext, useEffect } from '@wordpress/element';
import { CurrencyContext } from '@woocommerce/currency';

export const ShippingCurrencyContext = () => {
	const context = useContext( CurrencyContext );

	useEffect( () => {
		window.wc.ShippingCurrencyContext =
			window.wc.ShippingCurrencyContext || context;
	}, [ context ] );

	return null;
};
