/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
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

class ReportTable extends Component {
	render() {
		const {
			getHeadersContent,
			getRowsContent,
			getSummary,
			itemIdField,
			primaryData,
			tableData,
			totalsCountField,
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

		const headers = getHeadersContent();
		const orderedItems = orderBy( items, query.orderby, query.order );
		const ids = orderedItems.map( item => item[ itemIdField ] );
		const rows = getRowsContent( orderedItems );
		const totals = get( primaryData, [ 'data', 'totals' ], null );
		const summary = getSummary( totals );
		const totalRows = get( totals, [ totalsCountField ], items.length );

		return (
			<TableCard
				downloadable
				headers={ headers }
				ids={ ids }
				isLoading={ isRequesting }
				onQueryChange={ onQueryChange }
				rows={ rows }
				rowsPerPage={ query.per_page }
				summary={ summary }
				totalRows={ totalRows }
				{ ...tableProps }
			/>
		);
	}
}

ReportTable.propTypes = {
	/**
	 * The endpoint to use in API calls.
	 */
	endpoint: PropTypes.string.isRequired,
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
	itemIdField: PropTypes.string.isRequired,
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
	/**
	 * Name of the property in the primary data totals object which contains the
	 * total number of items.
	 */
	totalsCountField: PropTypes.string,
};

ReportTable.defaultProps = {
	getSummary: () => null,
	tableQuery: {},
	totalsCountField: '',
};

export default compose(
	withSelect( ( select, props ) => {
		const { endpoint, query, tableQuery } = props;
		// @TODO allow loading the primary data for the variations table once #926 is fixed.
		const primaryData =
			endpoint === 'variations' ? {} : getReportChartData( endpoint, 'primary', query, select );
		const tableData = getReportTableData( endpoint, query, select, tableQuery );

		return {
			primaryData,
			tableData,
		};
	} )
)( ReportTable );
