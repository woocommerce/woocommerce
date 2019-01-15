/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { get } from 'lodash';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { Card, EmptyTable, TableCard } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { getReportTableData } from 'store/reports/utils';
import withSelect from 'wc-api/with-select';
import './style.scss';

export class Leaderboard extends Component {
	render() {
		const {
			getHeadersContent,
			getRowsContent,
			isRequesting,
			isError,
			items,
			tableQuery,
			title,
		} = this.props;
		const data = get( items, [ 'data' ], [] );
		const rows = getRowsContent( data );
		const totalRows = tableQuery ? tableQuery.per_page : 5;

		if ( isError ) {
			return <ReportError className="woocommerce-leaderboard" isError />;
		}

		if ( ! isRequesting && rows.length === 0 ) {
			return (
				<Card title={ title } className="woocommerce-leaderboard">
					<EmptyTable>
						{ __( 'No data recorded for the selected time period.', 'wc-admin' ) }
					</EmptyTable>
				</Card>
			);
		}

		return (
			<TableCard
				className="woocommerce-leaderboard"
				headers={ getHeadersContent() }
				isLoading={ isRequesting }
				rows={ rows }
				rowsPerPage={ totalRows }
				showMenu={ false }
				title={ title }
				totalRows={ totalRows }
			/>
		);
	}
}

Leaderboard.propTypes = {
	/**
	 * The endpoint to use in API calls to populate the table rows and summary.
	 * For example, if `taxes` is provided, data will be fetched from the report
	 * `taxes` endpoint (ie: `/wc/v3/reports/taxes` and `/wc/v3/reports/taxes/stats`).
	 * If the provided endpoint doesn't exist, an error will be shown to the user
	 * with `ReportError`.
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
	 * Query args added to the report table endpoint request.
	 */
	query: PropTypes.object,
	/**
	 * Properties to be added to the query sent to the report table endpoint.
	 */
	tableQuery: PropTypes.object,
	/**
	 * String to display as the title of the table.
	 */
	title: PropTypes.string.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { endpoint, tableQuery, query } = props;
		const tableData = getReportTableData( endpoint, query, select, tableQuery );

		return { ...tableData };
	} )
)( Leaderboard );
