/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';
import moment from 'moment';

/**
 * WooCommerce dependencies
 */
import { defaultTableDateFormat, getCurrentDates } from '@woocommerce/date';
import { Date, Link } from '@woocommerce/components';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';

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
				key: 'product',
				isSortable: true,
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
		const persistedQuery = getPersistedQuery( query );

		return map( downloads, download => {
			const { _embedded, date, file_name, file_path, ip_address, order_id, product_id } = download;
			const { name: productName } = _embedded.product[ 0 ];
			const { name: userName } = _embedded.user[ 0 ];

			const productLink = getNewPath( persistedQuery, 'products', {
				filter: 'single_product',
				products: product_id,
			} );

			return [
				{
					display: <Date date={ date } visibleFormat={ defaultTableDateFormat } />,
					value: date,
				},
				{
					display: (
						<Link href={ productLink } type="wc-admin">
							{ productName }
						</Link>
					),
					value: productName,
				},
				{
					display: (
						<Link href={ file_path } type="external">
							{ file_name }
						</Link>
					),
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
					display: userName,
					value: userName,
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
		const { query } = this.props;
		const dates = getCurrentDates( query );
		const after = moment( dates.primary.after );
		const before = moment( dates.primary.before );
		const days = before.diff( after, 'days' ) + 1;

		return [
			{
				label: _n( 'day', 'days', days, 'wc-admin' ),
				value: numberFormat( days ),
			},
			{
				label: _n( 'download', 'downloads', totals.download_count, 'wc-admin' ),
				value: numberFormat( totals.download_count ),
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
				tableQuery={ {
					_embed: true,
				} }
				title={ __( 'Downloads', 'wc-admin' ) }
				columnPrefsKey="downloads_report_columns"
			/>
		);
	}
}
