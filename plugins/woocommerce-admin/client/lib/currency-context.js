/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import CurrencyFactory from '@woocommerce/currency';
import { CURRENCY } from '@woocommerce/wc-admin-settings';

const appCurrency = CurrencyFactory( CURRENCY );

export const getFilteredCurrencyInstance = ( query ) => {
	const config = appCurrency.getCurrencyConfig();
	const filteredConfig = applyFilters(
		'woocommerce_admin_report_currency',
		config,
		query
	);
	return CurrencyFactory( filteredConfig );
};

export const CurrencyContext = createContext(
	appCurrency // default value
);
