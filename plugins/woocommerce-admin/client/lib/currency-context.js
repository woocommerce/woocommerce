/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * WooCommerce dependencies
 */
import Currency from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { CURRENCY } from '@woocommerce/wc-admin-settings';

const appCurrency = Currency( CURRENCY );

export const getFilteredCurrencyInstance = ( query ) => {
	const config = appCurrency.getCurrency();
	const filteredConfig = applyFilters(
		'woocommerce_admin_report_currency',
		config,
		query
	);
	return new Currency( filteredConfig );
};

export const CurrencyContext = createContext(
	appCurrency // default value
);
