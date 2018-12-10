/** @format */
/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { get, orderBy } from 'lodash';
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

const TABLE_FILTER = 'woocommerce_admin_report_table';

class ReportTable extends Component {
	render() {
		const {
			getHeadersContent,
			getRowsContent,
			getSummary,
			itemIdField,
			primaryData,
			tableData,
			// These two props are not used in the render function, but are destructured
			// so they are not included in the `tableProps` variable.
			endpoint,
			tableQuery,
			...tableProps
		} = this.props;

		const { items, query } = tableData;

		const isError = tableData.isError || primaryData.isError;

		if ( isError ) {
			return <ReportError isError />;
		}

		const isRequesting = tableData.isRequesting || primaryData.isRequesting;
		const orderedItems = orderBy( items.data, query.orderby, query.order );
		const totals = get( primaryData, [ 'data', 'totals' ], null );
		const totalCount = items.totalCount || 0;
		const { headers, ids, rows, summary } = applyFilters( TABLE_FILTER, {
			endpoint: endpoint,
			headers: getHeadersContent(),
			orderedItems: orderedItems,
			ids: itemIdField ? orderedItems.map( item => item[ itemIdField ] ) : null,
			rows: getRowsContent( orderedItems ),
			totals: totals,
			summary: getSummary ? getSummary( totals, totalCount ) : null,
		} );

		return (
			<TableCard
				downloadable
				headers={ headers }
				ids={ ids }
				isLoading={ isRequesting }
				onQueryChange={ onQueryChange }
				rows={ rows }
				rowsPerPage={ parseInt( query.per_page ) }
				summary={ summary }
				totalRows={ totalCount }
				{ ...tableProps }
			/>
		);
	}
}

ReportTable.propTypes = {
	/**
	 * The endpoint to use in API calls.
	 */
	endpoint: PropTypes.string,
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
	 * Primary data of that report.
	 */
	primaryData: PropTypes.object.isRequired,
	/**
	 * Table data of that report.
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
		const { endpoint, getSummary, query, tableData, tableQuery } = props;
		const chartEndpoint = 'variations' === endpoint ? 'products' : endpoint;
		const primaryData = getSummary
			? getReportChartData( chartEndpoint, 'primary', query, select )
			: {};
		const queriedTableData = tableData || getReportTableData( endpoint, query, select, tableQuery );

		return {
			primaryData,
			tableData: queriedTableData,
		};
	} )
)( ReportTable );
