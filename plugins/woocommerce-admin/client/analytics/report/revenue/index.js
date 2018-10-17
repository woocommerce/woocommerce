/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { format as formatDate } from '@wordpress/date';
import { map } from 'lodash';
import PropTypes from 'prop-types';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Card, ReportFilters, TableCard, TablePlaceholder } from '@woocommerce/components';
import { downloadCSVFile, generateCSVDataFromTable, generateCSVFileName } from 'lib/csv';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { getAdminLink, onQueryChange } from 'lib/nav-utils';
import { getCurrentDates, getDateFormatsForInterval, getIntervalForQuery } from 'lib/date';
import OrdersReportChart from './chart';

export class RevenueReport extends Component {
	constructor() {
		super();
		this.onDownload = this.onDownload.bind( this );
	}

	onDownload( headers, rows, query ) {
		// @TODO The current implementation only downloads the contents displayed in the table.
		// Another solution is required when the data set is larger (see #311).
		return () => {
			downloadCSVFile(
				generateCSVFileName( 'revenue', query ),
				generateCSVDataFromTable( headers, rows )
			);
		};
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Date', 'wc-admin' ),
				key: 'date',
				required: true,
				defaultSort: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Gross Revenue', 'wc-admin' ),
				key: 'gross_revenue',
				required: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Refunds', 'wc-admin' ),
				key: 'refunds',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Coupons', 'wc-admin' ),
				key: 'coupons',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Taxes', 'wc-admin' ),
				key: 'taxes',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Shipping', 'wc-admin' ),
				key: 'shipping',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Net Revenue', 'wc-admin' ),
				key: 'net_revenue',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	getRowsContent( data = [] ) {
		const { query } = this.props;
		const currentInterval = getIntervalForQuery( query );
		const formats = getDateFormatsForInterval( currentInterval );

		return map( data, row => {
			const {
				coupons,
				gross_revenue,
				net_revenue,
				orders_count,
				refunds,
				shipping,
				taxes,
			} = row.subtotals;

			// @TODO How to create this per-report? Can use `w`, `year`, `m` to build time-specific order links
			// we need to know which kind of report this is, and parse the `label` to get this row's date
			const orderLink = (
				<a
					href={ getAdminLink(
						'edit.php?post_type=shop_order&m=' + formatDate( 'Ymd', row.date_start )
					) }
				>
					{ orders_count }
				</a>
			);
			return [
				{
					display: formatDate( formats.tableFormat, row.date_start ),
					value: row.date_start,
				},
				{
					display: orderLink,
					value: Number( orders_count ),
				},
				{
					display: formatCurrency( gross_revenue ),
					value: getCurrencyFormatDecimal( gross_revenue ),
				},
				{
					display: formatCurrency( refunds ),
					value: getCurrencyFormatDecimal( refunds ),
				},
				{
					display: formatCurrency( coupons ),
					value: getCurrencyFormatDecimal( coupons ),
				},
				{
					display: formatCurrency( taxes ),
					value: getCurrencyFormatDecimal( taxes ),
				},
				{
					display: formatCurrency( shipping ),
					value: getCurrencyFormatDecimal( shipping ),
				},
				{
					display: formatCurrency( net_revenue ),
					value: getCurrencyFormatDecimal( net_revenue ),
				},
			];
		} );
	}

	renderTable() {
		const { isTableDataRequesting, tableData, tableQuery } = this.props;
		const headers = this.getHeadersContent();

		if ( isTableDataRequesting ) {
			return (
				<Card
					title={ __( 'Revenue', 'wc-admin' ) }
					className="woocommerce-analytics__table-placeholder"
				>
					<TablePlaceholder
						caption={ __( 'Revenue', 'wc-admin' ) }
						headers={ headers }
						query={ tableQuery }
					/>
				</Card>
			);
		}

		const intervals = tableData.data.intervals;
		const rows = this.getRowsContent( intervals );

		const rowsPerPage =
			( tableQuery && tableQuery.per_page && parseInt( tableQuery.per_page ) ) || 25;

		return (
			<TableCard
				title={ __( 'Revenue', 'wc-admin' ) }
				rows={ rows }
				totalRows={ tableData.totalResults }
				rowsPerPage={ rowsPerPage }
				headers={ headers }
				onClickDownload={ this.onDownload( headers, rows, tableQuery ) }
				onQueryChange={ onQueryChange }
				query={ tableQuery }
				summary={ null }
			/>
		);
	}

	render() {
		const { path, query } = this.props;

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } />

				<OrdersReportChart query={ query } />
				{ this.renderTable() }
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
			per_page: query.per_page || 25,
			after: datesFromQuery.primary.after + 'T00:00:00+00:00',
			before: datesFromQuery.primary.before + 'T23:59:59+00:00',
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
