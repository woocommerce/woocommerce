/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getIntervalForQuery, getDateFormatsForInterval } from '@woocommerce/date';
import { Link } from '@woocommerce/components';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { numberFormat } from 'lib/number';

export default class CouponsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Date', 'wc-admin' ),
				key: 'date',
				defaultSort: true,
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Product Title', 'wc-admin' ),
				key: 'product_id',
				required: true,
			},
			{
				label: __( 'File Name', 'wc-admin' ),
				key: 'file_name',
			},
			{
				label: __( 'Order #', 'wc-admin' ),
				screenReaderLabel: __( 'Order ID', 'wc-admin' ),
				key: 'order_id',
			},
			{
				label: __( 'User Name', 'wc-admin' ),
				key: 'user_id',
			},
			{
				label: __( 'IP', 'wc-admin' ),
				key: 'ip_address',
			},
		];
	}

	getRowsContent( downloads ) {
		const { query } = this.props;
		const currentInterval = getIntervalForQuery( query );
		const { tableFormat } = getDateFormatsForInterval( currentInterval );
		const persistedQuery = getPersistedQuery( query );

		return map( downloads, download => {
			const { date, file_name, ip_address, order_id, product_id, user_id } = download;

			const productLink = getNewPath( persistedQuery, 'products', {
				filter: 'single_product',
				products: product_id,
			} );

			return [
				{
					display: formatDate( tableFormat, date ),
					value: date,
				},
				{
					display: (
						<Link href={ productLink } type="wc-admin">
							{ product_id }
						</Link>
					),
					value: product_id,
				},
				{
					display: file_name,
					value: file_name,
				},
				{
					display: (
						<Link href={ `post.php?post=${ order_id }&action=edit` } type="wp-admin">
							{ order_id }
						</Link>
					),
					value: order_id,
				},
				{
					display: user_id,
					value: user_id,
				},
				{
					display: ip_address,
					value: ip_address,
				},
			];
		} );
	}

	getSummary( totals ) {
		if ( ! totals ) {
			return [];
		}
		return [
			{
				label: _n( 'day', 'days', totals.days, 'wc-admin' ),
				value: numberFormat( totals.days ), // @TODO it's not defined
			},
			{
				label: _n( 'download', 'downloads', totals.downloads_count, 'wc-admin' ),
				value: numberFormat( totals.downloads_count ),
			},
		];
	}

	render() {
		const { query } = this.props;

		return (
			<ReportTable
				endpoint="downloads"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				query={ query }
				title={ __( 'Downloads', 'wc-admin' ) }
				columnPrefsKey="downloads_report_columns"
			/>
		);
	}
}
