/**
 * External dependencies
 */
import moment from 'moment';
import { find } from 'lodash';
import { getCurrentDates, appendTimestamp } from '@woocommerce/date';
import {
	getFilterQuery,
	SETTINGS_STORE_NAME,
	REPORTS_STORE_NAME,
} from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import { calculateDelta, formatValue } from '@woocommerce/number';
import { getAdminLink } from '@woocommerce/settings';

function getReportUrl( href, persistedQuery, primaryItem ) {
	if ( ! href ) {
		return '';
	}

	if ( href === '/jetpack' ) {
		return getAdminLink( 'admin.php?page=jetpack#/dashboard' );
	}

	return getNewPath( persistedQuery, href, {
		chart: primaryItem.chart,
	} );
}

export const getIndicatorValues = ( {
	indicator,
	primaryData,
	secondaryData,
	currency,
	formatAmount,
	persistedQuery,
} ) => {
	const primaryItem = find(
		primaryData.data,
		( data ) => data.stat === indicator.stat
	);
	const secondaryItem = find(
		secondaryData.data,
		( data ) => data.stat === indicator.stat
	);

	if ( ! primaryItem || ! secondaryItem ) {
		return {};
	}

	const href =
		( primaryItem._links &&
			primaryItem._links.report[ 0 ] &&
			primaryItem._links.report[ 0 ].href ) ||
		'';
	const reportUrl = getReportUrl( href, persistedQuery, primaryItem );
	const reportUrlType = href === '/jetpack' ? 'wp-admin' : 'wc-admin';
	const isCurrency = primaryItem.format === 'currency';

	const delta = calculateDelta( primaryItem.value, secondaryItem.value );
	const primaryValue = isCurrency
		? formatAmount( primaryItem.value )
		: formatValue( currency, primaryItem.format, primaryItem.value );
	const secondaryValue = isCurrency
		? formatAmount( secondaryItem.value )
		: formatValue( currency, secondaryItem.format, secondaryItem.value );
	return {
		primaryValue,
		secondaryValue,
		delta,
		reportUrl,
		reportUrlType,
	};
};

export const getIndicatorData = ( select, indicators, query, filters ) => {
	const { getReportItems, getReportItemsError, isResolving } =
		select( REPORTS_STORE_NAME );
	const { woocommerce_default_date_range: defaultDateRange } = select(
		SETTINGS_STORE_NAME
	).getSetting( 'wc_admin', 'wcAdminSettings' );
	const datesFromQuery = getCurrentDates( query, defaultDateRange );
	const endPrimary = datesFromQuery.primary.before;
	const endSecondary = datesFromQuery.secondary.before;
	const statKeys = indicators
		.map( ( indicator ) => indicator.stat )
		.join( ',' );
	const filterQuery = getFilterQuery( { filters, query } );
	const primaryQuery = {
		...filterQuery,
		after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
		before: appendTimestamp(
			endPrimary,
			endPrimary.isSame( moment(), 'day' ) ? 'now' : 'end'
		),
		stats: statKeys,
	};

	const secondaryQuery = {
		...filterQuery,
		after: appendTimestamp( datesFromQuery.secondary.after, 'start' ),
		before: appendTimestamp(
			endSecondary,
			endSecondary.isSame( moment(), 'day' ) ? 'now' : 'end'
		),
		stats: statKeys,
	};

	const primaryData = getReportItems(
		'performance-indicators',
		primaryQuery
	);
	const primaryError =
		getReportItemsError( 'performance-indicators', primaryQuery ) || null;
	const primaryRequesting = isResolving( 'getReportItems', [
		'performance-indicators',
		primaryQuery,
	] );

	const secondaryData = getReportItems(
		'performance-indicators',
		secondaryQuery
	);
	const secondaryError =
		getReportItemsError( 'performance-indicators', secondaryQuery ) || null;
	const secondaryRequesting = isResolving( 'getReportItems', [
		'performance-indicators',
		secondaryQuery,
	] );

	return {
		primaryData,
		primaryError,
		primaryRequesting,
		secondaryData,
		secondaryError,
		secondaryRequesting,
		defaultDateRange,
	};
};
