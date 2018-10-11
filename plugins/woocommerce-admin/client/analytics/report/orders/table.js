/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { map, orderBy } from 'lodash';

/**
 * Internal dependencies
 */
import { Card, OrderStatus, TableCard, TablePlaceholder } from '@woocommerce/components';
import { downloadCSVFile, generateCSVDataFromTable, generateCSVFileName } from 'lib/csv';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { getIntervalForQuery, getDateFormatsForInterval } from 'lib/date';
import { getAdminLink, onQueryChange } from 'lib/nav-utils';

export default class OrdersReportTable extends Component {
	constructor( props ) {
		super( props );
	}

	onDownload( headers, rows, query ) {
		// @TODO The current implementation only downloads the contents displayed in the table.
		// Another solution is required when the data set is larger (see #311).
		return () => {
			downloadCSVFile(
				generateCSVFileName( 'orders', query ),
				generateCSVDataFromTable( headers, rows )
			);
		};
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Date', 'wc-admin' ),
				key: 'date_created',
				required: true,
				defaultSort: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Order #', 'wc-admin' ),
				key: 'id',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Status', 'wc-admin' ),
				key: 'status',
				required: false,
				isSortable: true,
			},
			{
				label: __( 'Customer', 'wc-admin' ),
				key: 'customer_id',
				required: false,
				isSortable: true,
			},
			{
				label: __( 'Product(s)', 'wc-admin' ),
				key: 'products',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'Items Sold', 'wc-admin' ),
				key: 'items_sold',
				required: false,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Coupon(s)', 'wc-admin' ),
				key: 'coupons',
				required: false,
				isSortable: false,
			},
			{
				label: __( 'N. Revenue', 'wc-admin' ),
				key: 'net_revenue',
				required: true,
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	formatTableData( data ) {
		return map( data, row => {
			const {
				date_created,
				id,
				status,
				customer_id,
				line_items,
				coupon_lines,
				currency,
				total,
				total_tax,
				shipping_total,
				discount_total,
			} = row;

			return {
				date_created,
				id,
				status,
				customer_id,
				line_items,
				items_sold: line_items.reduce( ( acc, item ) => item.quantity + acc, 0 ),
				coupon_lines,
				currency,
				net_revenue: getCurrencyFormatDecimal(
					total - total_tax - shipping_total - discount_total
				),
			};
		} );
	}

	getRowsContent( tableData ) {
		const { query } = this.props;
		const currentInterval = getIntervalForQuery( query );
		const { tableFormat } = getDateFormatsForInterval( currentInterval );

		return map( tableData, row => {
			const {
				date_created,
				id,
				status,
				customer_id,
				line_items,
				items_sold,
				coupon_lines,
				currency,
				net_revenue,
			} = row;

			return [
				{
					display: formatDate( tableFormat, date_created ),
					value: date_created,
				},
				{
					display: <a href={ getAdminLink( 'post.php?post=' + id + '&action=edit' ) }>{ id }</a>,
					value: id,
				},
				{
					display: <OrderStatus order={ { status } } />,
					value: status,
				},
				{
					// @TODO This should display customer type (new/returning) once it's
					// implemented in the API.
					display: customer_id,
					value: customer_id,
				},
				{
					display: this.renderList(
						line_items.map( item => ( {
							href: getAdminLink( 'post.php?post=' + item.product_id + '&action=edit' ),
							label: item.name,
						} ) )
					),
					value: line_items
						.map( item => item.name )
						.join()
						.toLowerCase(),
				},
				{
					display: items_sold,
					value: items_sold,
				},
				{
					display: this.renderList(
						coupon_lines.map( coupon => ( {
							// @TODO It should link to the coupons report.
							href: getAdminLink( 'edit.php?s=' + coupon.code + '&post_type=shop_coupon' ),
							label: coupon.code,
						} ) )
					),
					value: coupon_lines
						.map( item => item.code )
						.join()
						.toLowerCase(),
				},
				{
					display: formatCurrency( net_revenue, currency ),
					value: net_revenue,
				},
			];
		} );
	}

	renderList( items ) {
		// @TODO Use ViewMore component if there are many items.
		return items.map( ( item, i ) => (
			<Fragment key={ i }>
				{ i > 0 ? ', ' : null }
				<a className={ items.length > 1 ? 'is-inline' : null } href={ item.href }>
					{ item.label }
				</a>
			</Fragment>
		) );
	}

	renderPlaceholderTable() {
		const headers = this.getHeadersContent();

		return (
			<Card
				title={ __( 'Orders', 'wc-admin' ) }
				className="woocommerce-analytics__table-placeholder"
			>
				<TablePlaceholder caption={ __( 'Orders last week', 'wc-admin' ) } headers={ headers } />
			</Card>
		);
	}

	renderTable() {
		const { orders, query } = this.props;

		const page = parseInt( query.page ) || 1;
		const rowsPerPage = parseInt( query.per_page ) || 25;
		const rows = this.getRowsContent(
			orderBy(
				this.formatTableData( orders ),
				query.orderby || 'date_created',
				query.order || 'asc'
			).slice( ( page - 1 ) * rowsPerPage, page * rowsPerPage )
		);

		const headers = this.getHeadersContent();

		const tableQuery = {
			...query,
			orderby: query.orderby || 'date_created',
			order: query.order || 'asc',
		};

		return (
			<TableCard
				title={ __( 'Orders last week', 'wc-admin' ) }
				rows={ rows }
				totalRows={ Object.keys( orders ).length }
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
		const { isRequesting } = this.props;

		return isRequesting ? this.renderPlaceholderTable() : this.renderTable();
	}
}
