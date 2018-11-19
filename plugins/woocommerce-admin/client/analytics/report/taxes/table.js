/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { get, map, orderBy } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { appendTimestamp, getCurrentDates } from '@woocommerce/date';
import { Link, TableCard } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { onQueryChange } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportError from 'analytics/components/report-error';
import { QUERY_DEFAULTS } from 'store/constants';
import { getReportChartData, getFilterQuery } from 'store/reports/utils';

class TaxesReportTable extends Component {
	getHeadersContent() {
		return [
			{
				label: __( 'Tax Code', 'wc-admin' ),
				// @TODO it should be the tax code, not the ID
				key: 'tax_rate_id',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Rate', 'wc-admin' ),
				key: 'rate',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Total Tax', 'wc-admin' ),
				key: 'total_tax',
				isSortable: true,
			},
			{
				label: __( 'Order Tax', 'wc-admin' ),
				key: 'order_tax',
				isSortable: true,
			},
			{
				label: __( 'Shipping Tax', 'wc-admin' ),
				key: 'shipping_tax',
				isSortable: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	getRowsContent( taxes ) {
		return map( taxes, tax => {
			const { order_tax, orders_count, tax_rate_id, total_tax, shipping_tax } = tax;

			// @TODO must link to the tax detail report
			const taxLink = (
				<Link href="" type="wc-admin">
					{ tax_rate_id }
				</Link>
			);

			return [
				// @TODO it should be the tax code, not the tax ID
				{
					display: taxLink,
					value: tax_rate_id,
				},
				{
					// @TODO add `rate` once it's returned by the API
					display: '',
					value: '',
				},
				{
					display: formatCurrency( total_tax ),
					value: getCurrencyFormatDecimal( total_tax ),
				},
				{
					display: formatCurrency( order_tax ),
					value: getCurrencyFormatDecimal( order_tax ),
				},
				{
					display: formatCurrency( shipping_tax ),
					value: getCurrencyFormatDecimal( shipping_tax ),
				},
				{
					display: orders_count,
					value: orders_count,
				},
			];
		} );
	}

	getSummary( totals ) {
		if ( ! totals ) {
			return [];
		}
		// @TODO the number of total rows should come from the API
		const totalRows = 0;
		return [
			{
				label: _n( 'tax code', 'tax codes', totalRows, 'wc-admin' ),
				value: totalRows,
			},
			{
				label: __( 'total tax', 'wc-admin' ),
				value: formatCurrency( totals.total_tax ),
			},
			{
				label: __( 'order tax', 'wc-admin' ),
				value: formatCurrency( totals.order_tax ),
			},
			{
				label: __( 'shipping tax', 'wc-admin' ),
				value: formatCurrency( totals.shipping_tax ),
			},
			{
				label: _n( 'order', 'orders', totals.orders_count, 'wc-admin' ),
				value: totals.orders_count,
			},
		];
	}

	render() {
		const { taxes, isTableDataError, isTableDataRequesting, primaryData, query } = this.props;

		const isError = isTableDataError || primaryData.isError;

		if ( isError ) {
			return <ReportError isError />;
		}

		const isRequesting = isTableDataRequesting || primaryData.isRequesting;

		const tableQuery = {
			...query,
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
		};

		const headers = this.getHeadersContent();
		const orderedTaxes = orderBy( taxes, tableQuery.orderby, tableQuery.order );
		const rows = this.getRowsContent( orderedTaxes );
		const rowsPerPage = parseInt( tableQuery.per_page ) || QUERY_DEFAULTS.pageSize;
		const totalRows = get( primaryData, [ 'data', 'totals', 'taxes_count' ], taxes.length );
		const summary = primaryData.data.totals ? this.getSummary( primaryData.data.totals ) : null;

		return (
			<TableCard
				title={ __( 'Taxes', 'wc-admin' ) }
				compareBy={ 'taxes' }
				ids={ orderedTaxes.map( tax => tax.tax_rate_id ) }
				rows={ rows }
				totalRows={ totalRows }
				rowsPerPage={ rowsPerPage }
				headers={ headers }
				isLoading={ isRequesting }
				onQueryChange={ onQueryChange }
				query={ tableQuery }
				summary={ summary }
				downloadable
			/>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const datesFromQuery = getCurrentDates( query );
		const primaryData = getReportChartData( 'taxes', 'primary', query, select );
		const filterQuery = getFilterQuery( 'taxes', query );

		const { getTaxes, isGetTaxesError, isGetTaxesRequesting } = select( 'wc-admin' );
		const tableQuery = {
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
			page: query.page || 1,
			per_page: query.per_page || QUERY_DEFAULTS.pageSize,
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
			...filterQuery,
		};
		const taxes = getTaxes( tableQuery );
		const isTableDataError = isGetTaxesError( tableQuery );
		const isTableDataRequesting = isGetTaxesRequesting( tableQuery );

		return {
			isTableDataError,
			isTableDataRequesting,
			taxes,
			primaryData,
		};
	} )
)( TaxesReportTable );
