/** @format */
/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { Component, createRef, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { focus } from '@wordpress/dom';
import { withDispatch } from '@wordpress/data';
import { get } from 'lodash';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { TableCard } from '@woocommerce/components';
import { onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { getReportChartData, getReportTableData } from 'wc-api/reports/utils';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';
import { extendTableData } from './utils';

import './style.scss';

const TABLE_FILTER = 'woocommerce_admin_report_table';

/**
 * Component that extends `TableCard` to facilitate its usage in reports.
 */
class ReportTable extends Component {
	constructor( props ) {
		super( props );

		this.onColumnsChange = this.onColumnsChange.bind( this );
		this.onPageChange = this.onPageChange.bind( this );
		this.scrollPointRef = createRef();
	}

	onColumnsChange( shownColumns ) {
		const { columnPrefsKey, getHeadersContent, updateCurrentUserData } = this.props;
		const columns = getHeadersContent().map( header => header.key );
		const hiddenColumns = columns.filter( column => ! shownColumns.includes( column ) );

		if ( columnPrefsKey ) {
			const userDataFields = {
				[ columnPrefsKey ]: hiddenColumns,
			};
			updateCurrentUserData( userDataFields );
		}
	}

	onPageChange() {
		this.scrollPointRef.current.scrollIntoView();
		const tableElement = this.scrollPointRef.current.nextSibling.querySelector(
			'.woocommerce-table__table'
		);
		const focusableElements = focus.focusable.find( tableElement );

		if ( focusableElements.length ) {
			focusableElements[ 0 ].focus();
		}
	}

	filterShownHeaders( headers, hiddenKeys ) {
		if ( ! hiddenKeys || ! hiddenKeys.length ) {
			return headers;
		}

		return headers.map( header => {
			const hidden = hiddenKeys.includes( header.key ) && ! header.required;
			return { ...header, hiddenByDefault: hidden };
		} );
	}

	render() {
		const {
			getHeadersContent,
			getRowsContent,
			getSummary,
			isRequesting,
			itemIdField,
			primaryData,
			tableData,
			// These props are not used in the render function, but are destructured
			// so they are not included in the `tableProps` variable.
			endpoint,
			tableQuery,
			userPrefColumns,
			...tableProps
		} = this.props;

		const { items, query } = tableData;

		const isError = tableData.isError || primaryData.isError;

		if ( isError ) {
			return <ReportError isError />;
		}

		const isLoading = isRequesting || tableData.isRequesting || primaryData.isRequesting;
		const totals = get( primaryData, [ 'data', 'totals' ], {} );
		const totalResults = items.totalResults;
		const downloadable = 0 < totalResults;

		/**
		 * Filter report table.
		 *
		 * Enables manipulation of data used to create a report table.
		 *
		 * @param {object} reportTableData - data used to create the table.
		 * @param {string} reportTableData.endpoint - table api endpoint.
		 * @param {array} reportTableData.headers - table headers data.
		 * @param {array} reportTableData.rows - table rows data.
		 * @param {object} reportTableData.totals - total aggregates for request.
		 * @param {array} reportTableData.summary - summary numbers data.
		 * @param {array} reportTableData.items - response from api requerst.
		 */
		const { headers, ids, rows, summary } = applyFilters( TABLE_FILTER, {
			endpoint,
			headers: getHeadersContent(),
			rows: getRowsContent( items.data ),
			totals,
			summary: getSummary ? getSummary( totals, totalResults ) : null,
			items,
		} );

		// Hide any headers based on user prefs, if loaded.
		const filteredHeaders = this.filterShownHeaders( headers, userPrefColumns );

		return (
			<Fragment>
				<div
					className="woocommerce-report-table__scroll-point"
					ref={ this.scrollPointRef }
					aria-hidden
				/>
				<TableCard
					downloadable={ downloadable }
					headers={ filteredHeaders }
					ids={ ids }
					isLoading={ isLoading }
					onQueryChange={ onQueryChange }
					onColumnsChange={ this.onColumnsChange }
					onPageChange={ this.onPageChange }
					rows={ rows }
					rowsPerPage={ parseInt( query.per_page ) || QUERY_DEFAULTS.pageSize }
					summary={ summary }
					totalRows={ totalResults }
					{ ...tableProps }
				/>
			</Fragment>
		);
	}
}

ReportTable.propTypes = {
	/**
	 * The key for user preferences settings for column visibility.
	 */
	columnPrefsKey: PropTypes.string,
	/**
	 * The endpoint to use in API calls to populate the table rows and summary.
	 * For example, if `taxes` is provided, data will be fetched from the report
	 * `taxes` endpoint (ie: `/wc/v4/reports/taxes` and `/wc/v4/reports/taxes/stats`).
	 * If the provided endpoint doesn't exist, an error will be shown to the user
	 * with `ReportError`.
	 */
	endpoint: PropTypes.string,
	/**
	 * Name of the methods available via `select( 'wc-api' )` that will be used to
	 * load more data for table items. If omitted, no call will be made and only
	 * the data returned by the reports endpoint will be used.
	 */
	extendItemsMethodNames: PropTypes.shape( {
		getError: PropTypes.string,
		isRequesting: PropTypes.string,
		load: PropTypes.string,
	} ),
	/**
	 * A function that returns the headers object to build the table.
	 */
	getHeadersContent: PropTypes.func.isRequired,
	/**
	 * A function that returns the rows array to build the table.
	 */
	getRowsContent: PropTypes.func.isRequired,
	/**
	 * A function that returns the summary object to build the table.
	 */
	getSummary: PropTypes.func,
	/**
	 * The name of the property in the item object which contains the id.
	 */
	itemIdField: PropTypes.string,
	/**
	 * Primary data of that report. If it's not provided, it will be automatically
	 * loaded via the provided `endpoint`.
	 */
	primaryData: PropTypes.object,
	/**
	 * Table data of that report. If it's not provided, it will be automatically
	 * loaded via the provided `endpoint`.
	 */
	tableData: PropTypes.object.isRequired,
	/**
	 * Properties to be added to the query sent to the report table endpoint.
	 */
	tableQuery: PropTypes.object,
	/**
	 * String to display as the title of the table.
	 */
	title: PropTypes.string.isRequired,
};

ReportTable.defaultProps = {
	primaryData: {},
	tableData: {
		items: {
			data: [],
			totalResults: 0,
		},
		query: {},
	},
	tableQuery: {},
};

export default compose(
	withSelect( ( select, props ) => {
		const {
			endpoint,
			getSummary,
			isRequesting,
			query,
			tableData,
			tableQuery,
			columnPrefsKey,
			filters,
			advancedFilters,
		} = props;

		let userPrefColumns = [];
		if ( columnPrefsKey ) {
			const { getCurrentUserData } = select( 'wc-api' );
			const userData = getCurrentUserData();

			userPrefColumns = userData[ columnPrefsKey ];
		}

		if ( isRequesting || ( query.search && ! ( query[ endpoint ] && query[ endpoint ].length ) ) ) {
			return {
				userPrefColumns,
			};
		}
		// Variations and Category charts are powered by the /reports/products/stats endpoint.
		const chartEndpoint = [ 'variations', 'categories' ].includes( endpoint )
			? 'products'
			: endpoint;
		const primaryData = getSummary
			? getReportChartData( {
					endpoint: chartEndpoint,
					dataType: 'primary',
					query,
					select,
					filters,
					advancedFilters,
					tableQuery,
				} )
			: {};
		const queriedTableData =
			tableData ||
			getReportTableData( { endpoint, query, select, tableQuery, filters, advancedFilters } );
		const extendedTableData = extendTableData( select, props, queriedTableData );

		return {
			primaryData,
			tableData: extendedTableData,
			query: { ...tableQuery, ...query },
			userPrefColumns,
		};
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( ReportTable );
