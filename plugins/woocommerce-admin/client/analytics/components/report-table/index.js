/** @format */
/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
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
import { getReportChartData, getReportTableData } from 'store/reports/utils';
import withSelect from 'wc-api/with-select';
import { extendTableData } from './utils';

const TABLE_FILTER = 'woocommerce_admin_report_table';

/**
 * Component that extends `TableCard` to facilitate its usage in reports.
 */
class ReportTable extends Component {
	constructor( props ) {
		super( props );

		this.onColumnsChange = this.onColumnsChange.bind( this );
	}

	onColumnsChange( shownColumns ) {
		const { columnPrefsKey, getHeadersContent } = this.props;
		const columns = getHeadersContent().map( header => header.key );
		const hiddenColumns = columns.filter( column => ! shownColumns.includes( column ) );

		if ( columnPrefsKey ) {
			const userDataFields = {
				[ columnPrefsKey ]: hiddenColumns,
			};
			this.props.updateCurrentUserData( userDataFields );
		}
	}

	filterShownHeaders( headers, hiddenKeys ) {
		if ( ! hiddenKeys ) {
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

		const isRequesting = tableData.isRequesting || primaryData.isRequesting;
		const totals = get( primaryData, [ 'data', 'totals' ], null );
		const totalResults = items.totalResults || 0;
		const { headers, ids, rows, summary } = applyFilters( TABLE_FILTER, {
			endpoint: endpoint,
			headers: getHeadersContent(),
			ids: itemIdField ? items.data.map( item => item[ itemIdField ] ) : null,
			rows: getRowsContent( items.data ),
			totals: totals,
			summary: getSummary ? getSummary( totals, totalResults ) : null,
		} );

		// Hide any headers based on user prefs, if loaded.
		const filteredHeaders = this.filterShownHeaders( headers, userPrefColumns );

		return (
			<TableCard
				downloadable
				headers={ filteredHeaders }
				ids={ ids }
				isLoading={ isRequesting }
				onQueryChange={ onQueryChange }
				onColumnsChange={ this.onColumnsChange }
				rows={ rows }
				rowsPerPage={ parseInt( query.per_page ) }
				summary={ summary }
				totalRows={ totalResults }
				{ ...tableProps }
			/>
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
	primaryData: PropTypes.object.isRequired,
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
	tableData: {},
	tableQuery: {},
};

export default compose(
	withSelect( ( select, props ) => {
		const { endpoint, getSummary, query, tableData, tableQuery, columnPrefsKey } = props;
		const chartEndpoint = 'variations' === endpoint ? 'products' : endpoint;
		const primaryData = getSummary
			? getReportChartData( chartEndpoint, 'primary', query, select )
			: {};
		const queriedTableData = tableData || getReportTableData( endpoint, query, select, tableQuery );
		const extendedTableData = extendTableData( select, props, queriedTableData );

		const selectProps = {
			primaryData,
			tableData: extendedTableData,
		};

		if ( columnPrefsKey ) {
			const { getCurrentUserData } = select( 'wc-api' );
			const userData = getCurrentUserData();

			selectProps.userPrefColumns = userData[ columnPrefsKey ];
		}

		return selectProps;
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( ReportTable );
