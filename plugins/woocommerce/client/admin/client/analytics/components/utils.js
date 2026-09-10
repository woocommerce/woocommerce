/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { usesServerSideSearch } from '@woocommerce/data';

/**
 * Whether a report should render its empty search state.
 *
 * Only reports that resolve the search client side can know this: their query carries the
 * matching item IDs, so an active search without any means nothing matched.
 *
 * @param {Object} query   Current query object.
 * @param {Array}  limitBy Properties used to limit the results, search subject first.
 * @return {boolean} True when the search is known to have matched nothing.
 */
export function hasEmptySearchResults( query, limitBy ) {
	if ( ! query.search || usesServerSideSearch( limitBy ) ) {
		return false;
	}

	// The resolved IDs land on the search subject. Any further limit property is an
	// independent filter and says nothing about whether the term matched.
	const [ searchSubject ] = limitBy;
	if ( ! searchSubject ) {
		return false;
	}

	return ! ( query[ searchSubject ] && query[ searchSubject ].length );
}

/**
 * Returns the message explaining why a report has nothing to show.
 *
 * @param {Object} query   Current query object.
 * @param {Array}  limitBy Properties used to limit the results, search subject first.
 * @return {string} Message to render in place of the report.
 */
export function getEmptyMessage( query, limitBy ) {
	if ( hasEmptySearchResults( query, limitBy ) ) {
		// The client resolved the search itself, so it knows the term is what matched nothing.
		return __( 'No data for the current search', 'woocommerce' );
	}

	if ( query.search && usesServerSideSearch( limitBy ) ) {
		// The endpoint answers the same way whether the term matched nothing or matched items
		// without data in the period, so name both rather than blame the wrong one.
		return __(
			'No data for the current search in the selected date range',
			'woocommerce'
		);
	}

	return __( 'No data for the selected date range', 'woocommerce' );
}

/**
 * Whether displayed totals, intervals or segments contain missing conversions.
 *
 * @param {Object} data Report data.
 * @return {boolean} Whether any monetary contribution is unqualified.
 */
function hasMissingReportData( data, field ) {
	const isIncomplete = ( subtotal ) =>
		Number( subtotal?.[ field ] ) > 0 ||
		Boolean(
			subtotal?.segments?.some( ( segment ) =>
				isIncomplete( segment.subtotals )
			)
		);
	return (
		isIncomplete( data?.totals ) ||
		Boolean(
			data?.intervals?.some( ( interval ) =>
				isIncomplete( interval.subtotals )
			)
		)
	);
}

/** Whether a report contains missing historical conversion data. */
export function hasMissingCurrencyData( data ) {
	return hasMissingReportData( data, 'reporting_missing_orders' );
}

/** Whether a coupon product breakdown lacks historical allocations. */
export function hasMissingCouponAllocationData( data ) {
	return hasMissingReportData( data, 'allocation_missing_orders' );
}
