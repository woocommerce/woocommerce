/**
 * External dependencies
 */
import { useContext, useEffect } from '@wordpress/element';
import { CurrencyContext } from '@woocommerce/currency';
import { numberFormat } from '@woocommerce/number';
import { applyFilters } from '@wordpress/hooks';

export const ShippingCurrencyContext = () => {
	const context = useContext( CurrencyContext );

	useEffect( () => {
		window.wc.ShippingCurrencyContext =
			window.wc.ShippingCurrencyContext || applyFilters( 'woocommerce_shipping_zone_method_currency_context', context );
		window.wc.ShippingCurrencyNumberFormat =
			window.wc.ShippingCurrencyNumberFormat || numberFormat;
	}, [ context ] );

	return null;
};
