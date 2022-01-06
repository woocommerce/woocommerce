/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import CurrencyFactory from '@woocommerce/currency';
/**
 * Internal dependencies
 */
import { CURRENCY } from '~/utils/admin-settings';

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
