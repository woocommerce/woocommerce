/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { withSelect } from '@wordpress/data';
import { map } from 'lodash';
import moment from 'moment';
import { Date, Link } from '@woocommerce/components';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { formatValue } from '@woocommerce/number';
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { getCurrentDates, defaultTableDateFormat } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import ReportTable from '../../components/report-table';
import { CurrencyContext } from '../../../lib/currency-context';

class DownloadsReportTable extends Component {
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
				label: __( 'Product title', 'woocommerce-admin' ),
				key: 'product',
				isSortable: true,
				required: true,
			},
			{
				label: __( 'File name', 'woocommerce-admin' ),
				key: 'file_name',
			},
			{
				label: __( 'Order #', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Order Number', 'woocommerce-admin' ),
				key: 'order_number',
			},
			{
				label: __( 'Username', 'woocommerce-admin' ),
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
		const dateFormat = getSetting( 'dateFormat', defaultTableDateFormat );

		return map( downloads, ( download ) => {
			const {
				_embedded,
				date,
				file_name: fileName,
				file_path: filePath,
				ip_address: ipAddress,
				order_id: orderId,
				order_number: orderNumber,
				product_id: productId,
				username,
			} = download;

			const {
				code: errorCode,
				name: productName,
			} = _embedded.product[ 0 ];
			let productDisplay, productValue;

			// Handle deleted products.
			if ( errorCode === 'woocommerce_rest_product_invalid_id' ) {
				productDisplay = __( '(Deleted)', 'woocommerce-admin' );
				productValue = __( '(Deleted)', 'woocommerce-admin' );
			} else {
				const productURL = getNewPath(
					persistedQuery,
					'/analytics/products',
					{
						filter: 'single_product',
						products: productId,
					}
				);

				productDisplay = (
					<Link href={ productURL } type="wc-admin">
						{ productName }
					</Link>
				);
				productValue = productName;
			}

			return [
				{
					display: (
						<Date date={ date } visibleFormat={ dateFormat } />
					),
					value: date,
				},
				{
					display: productDisplay,
					value: productValue,
				},
				{
					display: (
						<Link href={ filePath } type="external">
							{ fileName }
						</Link>
					),
					value: fileName,
				},
				{
					display: (
						<Link
							href={ getAdminLink(
								`post.php?post=${ orderId }&action=edit`
							) }
							type="wp-admin"
						>
							{ orderNumber }
						</Link>
					),
					value: orderNumber,
				},
				{
					display: username,
					value: username,
				},
				{
					display: ipAddress,
					value: ipAddress,
				},
			];
		} );
	}

	getSummary( totals ) {
		const { download_count: downloadCount = 0 } = totals;
		const { query, defaultDateRange } = this.props;
		const dates = getCurrentDates( query, defaultDateRange );
		const after = moment( dates.primary.after );
		const before = moment( dates.primary.before );
		const days = before.diff( after, 'days' ) + 1;
		const currency = this.context.getCurrencyConfig();

		return [
			{
				label: _n( 'day', 'days', days, 'woocommerce-admin' ),
				value: formatValue( currency, 'number', days ),
			},
			{
				label: _n(
					'Download',
					'Downloads',
					downloadCount,
					'woocommerce-admin'
				),
				value: formatValue( currency, 'number', downloadCount ),
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
				summaryFields={ [ 'download_count' ] }
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

DownloadsReportTable.contextType = CurrencyContext;

export default withSelect( ( select ) => {
	const { woocommerce_default_date_range: defaultDateRange } = select(
		SETTINGS_STORE_NAME
	).getSetting( 'wc_admin', 'wcAdminSettings' );
	return { defaultDateRange };
} )( DownloadsReportTable );
