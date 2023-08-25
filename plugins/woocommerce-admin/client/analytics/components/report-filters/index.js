/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
import PropTypes from 'prop-types';
import { omitBy, isUndefined, snakeCase } from 'lodash';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';
import { ReportFilters as Filters } from '@woocommerce/components';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import {
	getCurrentDates,
	getDateParamsFromQuery,
	isoDateFormat,
} from '@woocommerce/date';
import { recordEvent } from '@woocommerce/tracks';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { LOCALE } from '~/utils/admin-settings';

function ReportFilters( {
	report,
	advancedFilters,
	filters,
	path,
	query,
	showDatePicker,
} ) {
	const Currency = useContext( CurrencyContext );
	const { defaultDateRange } = useSelect( ( select ) => {
		const { woocommerce_default_date_range } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );
		return { defaultDateRange: woocommerce_default_date_range };
	} );
	const { addCesSurveyForAnalytics } = useDispatch( CES_STORE_KEY );

	function onDateSelect( data ) {
		addCesSurveyForAnalytics();
		recordEvent( 'datepicker_update', {
			report,
			...omitBy( data, isUndefined ),
		} );
	}

	function onFilterSelect( data ) {
		// This event gets triggered in the following cases.
		// 1. Select "Single product" and choose a product.
		// 2. Select "Comparison" or any other filter types.
		// The comparison and other filter types require a user to click
		// a button to execute a query, so this is not a good place to
		// trigger a CES survey for those.
		const triggerCesFor = [
			'single_product',
			'single_category',
			'single_coupon',
			'single_variation',
		];
		const filterName = data.filter || data[ 'filter-variations' ];
		if ( triggerCesFor.includes( filterName ) ) {
			addCesSurveyForAnalytics();
		}

		const eventProperties = {
			report,
			filter: data.filter || 'all',
		};

		if ( data.filter === 'single_product' ) {
			eventProperties.filter_variation =
				data[ 'filter-variations' ] || 'all';
		}

		recordEvent( 'analytics_filter', eventProperties );
	}

	function onAdvancedFilterAction( action, data ) {
		switch ( action ) {
			case 'add':
				recordEvent( 'analytics_filters_add', {
					report,
					filter: data.key,
				} );
				break;
			case 'remove':
				recordEvent( 'analytics_filters_remove', {
					report,
					filter: data.key,
				} );
				break;
			case 'filter':
				const snakeCaseData = Object.keys( data ).reduce(
					( result, property ) => {
						result[ snakeCase( property ) ] = data[ property ];
						return result;
					},
					{}
				);
				addCesSurveyForAnalytics();
				recordEvent( 'analytics_filters_filter', {
					report,
					...snakeCaseData,
				} );
				break;
			case 'clear_all':
				recordEvent( 'analytics_filters_clear_all', { report } );
				break;
			case 'match':
				recordEvent( 'analytics_filters_all_any', {
					report,
					value: data.match,
				} );
				break;
		}
	}

	const { period, compare, before, after } = getDateParamsFromQuery(
		query,
		defaultDateRange
	);
	const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates(
		query,
		defaultDateRange
	);
	const dateQuery = {
		period,
		compare,
		before,
		after,
		primaryDate,
		secondaryDate,
	};

	return (
		<Filters
			query={ query }
			siteLocale={ LOCALE.siteLocale }
			currency={ Currency.getCurrencyConfig() }
			path={ path }
			filters={ filters }
			advancedFilters={ advancedFilters }
			showDatePicker={ showDatePicker }
			onDateSelect={ onDateSelect }
			onFilterSelect={ onFilterSelect }
			onAdvancedFilterAction={ onAdvancedFilterAction }
			dateQuery={ dateQuery }
			isoDateFormat={ isoDateFormat }
		/>
	);
}

ReportFilters.propTypes = {
	/**
	 * Config option passed through to `AdvancedFilters`
	 */
	advancedFilters: PropTypes.object,
	/**
	 * Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.
	 */
	filters: PropTypes.array,
	/**
	 * The `path` parameter supplied by React-Router
	 */
	path: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form
	 */
	query: PropTypes.object,
	/**
	 * Whether the date picker must be shown..
	 */
	showDatePicker: PropTypes.bool,
	/**
	 * The report where filter are placed.
	 */
	report: PropTypes.string.isRequired,
};

export default ReportFilters;
