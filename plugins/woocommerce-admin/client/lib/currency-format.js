/** @format */
/**
 * Internal dependencies
 */
import { CURRENCY } from '@woocommerce/wc-admin-settings';

/**
 * WooCommerce dependencies
 */
import Currency from '@woocommerce/currency';

// Pass the site currency settings to our instance.
const storeCurrency = new Currency( CURRENCY );

// Allow our exported API to be called without knowing about the Currency instance.
const formatCurrency = storeCurrency.formatCurrency.bind( storeCurrency );
const formatDecimal = storeCurrency.formatDecimal.bind( storeCurrency );
const formatDecimalString = storeCurrency.formatDecimalString.bind( storeCurrency );
const render = storeCurrency.render.bind( storeCurrency );
const setCurrency = storeCurrency.setCurrency.bind( storeCurrency );

// Export the expected API for the consuming app, along with the instance.
export {
	storeCurrency as Currency,
	formatCurrency,
	formatDecimal as getCurrencyFormatDecimal,
	formatDecimalString as getCurrencyFormatString,
	render as renderCurrency,
	setCurrency,
};
