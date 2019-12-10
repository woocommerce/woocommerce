/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { find } from 'lodash';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AdvancedFilters from '../advanced-filters';
import { CompareFilter } from '../compare-filter';
import DateRangeFilterPicker from '../date-range-filter-picker';
import FilterPicker from '../filter-picker';
import { H, Section } from '../section';

/**
 * Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters`
 * or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.
 *
 * @return { object } -
 */
class ReportFilters extends Component {
	constructor() {
		super();
		this.renderCard = this.renderCard.bind( this );
		this.onRangeSelect = this.onRangeSelect.bind( this );
	}

	renderCard( config ) {
		const {
			siteLocale,
			advancedFilters,
			query,
			path,
			onAdvancedFilterAction,
			currency,
		} = this.props;
		const { filters, param } = config;
		if ( ! query[ param ] ) {
			return null;
		}

		if ( 0 === query[ param ].indexOf( 'compare' ) ) {
			const filter = find( filters, { value: query[ param ] } );
			if ( ! filter ) {
				return null;
			}
			const { settings = {} } = filter;
			return (
				<div key={ param } className="woocommerce-filters__advanced-filters">
					<CompareFilter path={ path } query={ query } { ...settings } />
				</div>
			);
		}
		if ( 'advanced' === query[ param ] ) {
			return (
				<div key={ param } className="woocommerce-filters__advanced-filters">
					<AdvancedFilters
						siteLocale={ siteLocale }
						currency={ currency }
						config={ advancedFilters }
						path={ path }
						query={ query }
						onAdvancedFilterAction={ onAdvancedFilterAction }
					/>
				</div>
			);
		}
	}

	onRangeSelect( data ) {
		const { query, path, onDateSelect } = this.props;
		updateQueryString( data, path, query );
		onDateSelect( data );
	}

	render() {
		const {
			dateQuery,
			filters,
			query,
			path,
			showDatePicker,
			onFilterSelect,
			isoDateFormat,
		} = this.props;
		return (
			<Fragment>
				<H className="screen-reader-text">{ __( 'Filters', 'woocommerce-admin' ) }</H>
				<Section component="div" className="woocommerce-filters">
					<div className="woocommerce-filters__basic-filters">
						{ showDatePicker && (
							<DateRangeFilterPicker
								key={ JSON.stringify( query ) }
								dateQuery={ dateQuery }
								onRangeSelect={ this.onRangeSelect }
								isoDateFormat={ isoDateFormat }
							/>
						) }
						{ filters.map( config => {
							if ( config.showFilters( query ) ) {
								return (
									<FilterPicker
										key={ config.param }
										config={ config }
										query={ query }
										path={ path }
										onFilterSelect={ onFilterSelect }
									/>
								);
							}
						} ) }
					</div>
					{ filters.map( this.renderCard ) }
				</Section>
			</Fragment>
		);
	}
}

ReportFilters.propTypes = {
	/**
	 * The locale of the site (passed through to `AdvancedFilters`)
	 */
	siteLocale: PropTypes.string,
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
	 * Whether the date picker must be shown.
	 */
	showDatePicker: PropTypes.bool,
	/**
	 * Function to be called after date selection.
	 */
	onDateSelect: PropTypes.func,
	/**
	 * Function to be called after filter selection.
	 */
	onFilterSelect: PropTypes.func,
	/**
	 * Function to be called after an advanced filter action has been taken.
	 */
	onAdvancedFilterAction: PropTypes.func,
	/**
	 * The currency formatting instance for the site.
	 */
	currency: PropTypes.object.isRequired,
	/**
	 * The date query string represented in object form.
	 */
	dateQuery: PropTypes.shape( {
		period: PropTypes.string.isRequired,
		compare: PropTypes.string.isRequired,
		before: PropTypes.object,
		after: PropTypes.object,
		primaryDate: PropTypes.shape( {
			label: PropTypes.string.isRequired,
			range: PropTypes.string.isRequired,
		} ).isRequired,
		secondaryDate: PropTypes.shape( {
			label: PropTypes.string.isRequired,
			range: PropTypes.string.isRequired,
		} ).isRequired,
	} ),
	/**
	 * ISO date format string.
	 */
	isoDateFormat: PropTypes.string,
};

ReportFilters.defaultProps = {
	siteLocale: 'en_US',
	advancedFilters: {},
	filters: [],
	query: {},
	showDatePicker: true,
	onDateSelect: () => {},
};

export default ReportFilters;
