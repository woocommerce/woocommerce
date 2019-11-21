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
import { formatValue } from 'lib/number-format';

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
				label: __( 'Date', 'woocommerce-admin' ),
				key: 'date',
				defaultSort: true,
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Product Title', 'woocommerce-admin' ),
				key: 'product',
				isSortable: true,
				required: true,
			},
			{
				label: __( 'File Name', 'woocommerce-admin' ),
				key: 'file_name',
			},
			{
				label: __( 'Order #', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Order Number', 'woocommerce-admin' ),
				key: 'order_number',
			},
			{
				label: __( 'User Name', 'woocommerce-admin' ),
				key: 'user_id',
			},
			{
				label: __( 'IP', 'woocommerce-admin' ),
				key: 'ip_address',
			},
		];
	}

	getRowsContent( downloads ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );

		return map( downloads, download => {
			const {
				_embedded,
				date,
				file_name,
				file_path,
				ip_address,
				order_id,
				order_number,
				product_id,
				username,
			} = download;
			const { name: productName } = _embedded.product[ 0 ];

			const productLink = getNewPath( persistedQuery, '/analytics/products', {
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
							{ order_number }
						</Link>
					),
					value: order_number,
				},
				{
					display: username,
					value: username,
				},
				{
					display: ip_address,
					value: ip_address,
				},
			];
		} );
	}

	getSummary( totals ) {
		const { download_count = 0 } = totals;
		const { query } = this.props;
		const dates = getCurrentDates( query );
		const after = moment( dates.primary.after );
		const before = moment( dates.primary.before );
		const days = before.diff( after, 'days' ) + 1;

		return [
			{
				label: _n( 'day', 'days', days, 'woocommerce-admin' ),
				value: formatValue( 'number', days ),
			},
			{
				label: _n( 'download', 'downloads', download_count, 'woocommerce-admin' ),
				value: formatValue( 'number', download_count ),
			},
		];
	}

	render() {
		const { query, filters, advancedFilters } = this.props;

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
				title={ __( 'Downloads', 'woocommerce-admin' ) }
				columnPrefsKey="downloads_report_columns"
				filters={ filters }
				advancedFilters={ advancedFilters }
			/>
		);
	}
}
