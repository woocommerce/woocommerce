/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { map } from 'lodash';

/**
 * Internal dependencies
 */
import { Card, Link, TableCard, TablePlaceholder } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { getDateFormatsForInterval, getIntervalForQuery } from 'lib/date';
import { onQueryChange } from 'lib/nav-utils';
import ReportError from 'analytics/components/report-error';

export default class RevenueReportTable extends Component {
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
				<Link
					href={ 'edit.php?post_type=shop_order&m=' + formatDate( 'Ymd', row.date_start ) }
					type="wp-admin"
				>
					{ orders_count }
				</Link>
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

	renderPlaceholderTable( tableQuery ) {
		const headers = this.getHeadersContent();

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

	renderTable( tableQuery ) {
		const { tableData, totalRows } = this.props;

		const rowsPerPage =
			( tableQuery && tableQuery.per_page && parseInt( tableQuery.per_page ) ) || 25;
		const rows = this.getRowsContent( tableData.data.intervals );

		const headers = this.getHeadersContent();

		return (
			<TableCard
				title={ __( 'Revenue', 'wc-admin' ) }
				rows={ rows }
				totalRows={ totalRows }
				rowsPerPage={ rowsPerPage }
				headers={ headers }
				onQueryChange={ onQueryChange }
				query={ tableQuery }
				summary={ null }
				downloadable
			/>
		);
	}

	render() {
		const { isError, isRequesting, query } = this.props;

		if ( isError ) {
			return <ReportError isError />;
		}

		const tableQuery = {
			...query,
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
		};

		if ( isRequesting ) {
			return this.renderPlaceholderTable( tableQuery );
		}

		return this.renderTable( tableQuery );
	}
}
