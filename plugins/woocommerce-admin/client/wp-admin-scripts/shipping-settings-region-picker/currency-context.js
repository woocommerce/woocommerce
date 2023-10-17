/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
import { CurrencyContext } from '@woocommerce/currency';

export const ShippingCurrencyContext = () => {
    window.wc.ShippingCurrencyContext = window.wc.ShippingCurrencyContext || useContext( CurrencyContext );
    return null;
};
