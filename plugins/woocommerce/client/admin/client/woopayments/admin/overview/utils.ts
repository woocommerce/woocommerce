/**
 * Internal dependencies
 */
import type {
	WooPaymentsDeposit,
	WooPaymentsDepositsOverview,
	WooPaymentsMoneyAmount,
} from './types';

export const getSettingsPaymentsProviderRouteUrl = ( path: string ) => {
	const adminUrl = window.wcSettings?.adminUrl || '';
	const separator = adminUrl.endsWith( '/' ) || adminUrl === '' ? '' : '/';

	return `${ adminUrl }${ separator }admin.php?page=wc-settings&tab=checkout&path=${ encodeURIComponent(
		path
	) }`;
};

export const normalizeCurrencyCode = ( currency?: string | null ) =>
	( currency || 'usd' ).toUpperCase();

export const formatWooPaymentsAmount = (
	amount: number,
	currency?: string | null
) => {
	const currencyCode = normalizeCurrencyCode( currency );

	try {
		return new Intl.NumberFormat( undefined, {
			style: 'currency',
			currency: currencyCode,
		} ).format( amount / 100 );
	} catch ( _error ) {
		return `${ ( amount / 100 ).toFixed( 2 ) } ${ currencyCode }`;
	}
};

export const getDefaultCurrency = (
	overview: WooPaymentsDepositsOverview | null
) => {
	const accountCurrency = overview?.account.default_currency;
	if ( accountCurrency ) {
		return accountCurrency.toLowerCase();
	}

	return (
		overview?.balance?.available?.[ 0 ]?.currency ||
		overview?.balance?.pending?.[ 0 ]?.currency ||
		'usd'
	).toLowerCase();
};

export const getAmountForCurrency = (
	amounts: WooPaymentsMoneyAmount[] | undefined,
	currency: string
) =>
	amounts?.find(
		( amount ) => amount.currency.toLowerCase() === currency.toLowerCase()
	)?.amount ?? 0;

export const formatPayoutDate = ( deposit: WooPaymentsDeposit ) => {
	const rawDate = deposit.date || deposit.created || '';
	const timestamp =
		typeof rawDate === 'number' && rawDate < 10000000000
			? rawDate * 1000
			: rawDate;
	const date = new Date( timestamp );

	if ( Number.isNaN( date.getTime() ) ) {
		return '-';
	}

	return date.toLocaleDateString( undefined, {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	} );
};

export const formatPayoutStatus = ( status: string ) =>
	status
		.replace( /_/g, ' ' )
		.replace( /^\w/, ( match ) => match.toUpperCase() );
