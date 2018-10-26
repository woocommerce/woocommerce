/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { withSelect } from '@wordpress/data';
import { get } from 'lodash';

/**
 * Internal dependencies
 */
import { ReportFilters } from '@woocommerce/components';
import { appendTimestamp, getCurrentDates } from 'lib/date';
import { QUERY_DEFAULTS } from 'store/constants';
import RevenueReportChart from './chart';
import RevenueReportTable from './table';

export class RevenueReport extends Component {
	constructor() {
		super();
	}

	render() {
		const { isTableDataError, isTableDataRequesting, path, query, tableData } = this.props;

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } />
				<RevenueReportChart query={ query } />
				<RevenueReportTable
					isError={ isTableDataError }
					isRequesting={ isTableDataRequesting }
					tableData={ tableData }
					query={ query }
					totalRows={ get( tableData, [ 'totalResults' ], 0 ) }
				/>
			</Fragment>
		);
	}
}

RevenueReport.propTypes = {
	params: PropTypes.object.isRequired,
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const { getReportStats, isReportStatsRequesting, isReportStatsError } = select( 'wc-admin' );
		const datesFromQuery = getCurrentDates( query );

		// TODO Support hour here when viewing a single day
		const tableQuery = {
			interval: 'day',
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
			page: query.page || 1,
			per_page: query.per_page || QUERY_DEFAULTS.pageSize,
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
		};
		const tableData = getReportStats( 'revenue', tableQuery );
		const isTableDataError = isReportStatsError( 'revenue', tableQuery );
		const isTableDataRequesting = isReportStatsRequesting( 'revenue', tableQuery );

		return {
			tableQuery,
			tableData,
			isTableDataError,
			isTableDataRequesting,
		};
	} )
)( RevenueReport );
