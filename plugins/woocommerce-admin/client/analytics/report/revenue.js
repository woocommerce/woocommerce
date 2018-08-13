/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { map, noop } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import DatePicker from 'components/date-picker';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { getAdminLink, updateQueryString } from 'lib/nav-utils';
import { getReportData } from 'lib/swagger';
import Header from 'layout/header';
import { SummaryList, SummaryNumber } from 'components/summary';
import { TableCard } from 'components/table';

// Mock data until we fetch from an API
import rawData from './mock-data';

class RevenueReport extends Component {
	constructor() {
		super();
		this.onQueryChange = this.onQueryChange.bind( this );

		// TODO remove this when we implement real endpoints
		this.state = { stats: {} };
	}

	componentDidMount() {
		// Swagger doesn't support returning different data based on query args
		// this is more or less to show how we will manipulate data calls based on props
		const statsQueryArgs = {
			interval: 'week',
			after: '2018-04-22',
			before: '2018-05-06',
		};

		getReportData( 'revenue/stats', statsQueryArgs ).then( response => {
			if ( ! response.ok ) {
				return;
			}

			response.json().then( () => {
				// Ignore data, just use our fake data once we have a response
				this.setState( { stats: rawData } );
			} );
		} );
	}

	/**
	 * This function returns an event handler for the given `param`
	 * @todo Move handling of this to a library?
	 * @param {string} param The parameter in the querystring which should be updated (ex `page`, `per_page`)
	 * @return {function} A callback which will update `param` to the passed value when called.
	 */
	onQueryChange( param ) {
		switch ( param ) {
			case 'sort':
				return ( key, dir ) => updateQueryString( { orderby: key, order: dir } );
			default:
				return value => updateQueryString( { [ param ]: value } );
		}
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Date', 'wc-admin' ),
				key: 'date_start',
				required: true,
				defaultSort: true,
				isSortable: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: false,
				isSortable: true,
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
				isSortable: false, // For example
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
				<a href={ getAdminLink( '/edit.php?post_type=shop_order&w=4&year=2018' ) }>
					{ orders_count }
				</a>
			);
			return [
				{
					display: formatDate( 'm/d/Y', row.date_start ),
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

	getSummaryContent( data = {} ) {
		return [
			{
				label: __( 'gross revenue', 'wc-admin' ),
				value: formatCurrency( data.gross_revenue ),
			},
			{
				label: __( 'refunds', 'wc-admin' ),
				value: formatCurrency( data.refunds ),
			},
			{
				label: __( 'coupons', 'wc-admin' ),
				value: formatCurrency( data.coupons ),
			},
			{
				label: __( 'taxes', 'wc-admin' ),
				value: formatCurrency( data.taxes ),
			},
			{
				label: __( 'shipping', 'wc-admin' ),
				value: formatCurrency( data.shipping ),
			},
			{
				label: __( 'net revenue', 'wc-admin' ),
				value: formatCurrency( data.net_revenue ),
			},
		];
	}

	render() {
		const { path, query } = this.props;
		const { totals = {}, intervals = [] } = this.state.stats;
		const summary = this.getSummaryContent( totals ) || [];
		const rows = this.getRowsContent( intervals ) || [];
		const headers = this.getHeadersContent();

		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics', __( 'Analytics', 'wc-admin' ) ],
						__( 'Revenue', 'wc-admin' ),
					] }
				/>
				<DatePicker query={ query } path={ path } key={ JSON.stringify( query ) } />

				<SummaryList>
					<SummaryNumber
						value={ formatCurrency( totals.gross_revenue ) }
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						delta={ 29 }
					/>
					<SummaryNumber
						value={ formatCurrency( totals.refunds ) }
						label={ __( 'Refunds', 'wc-admin' ) }
						delta={ -10 }
						selected
					/>
					<SummaryNumber
						value={ formatCurrency( totals.coupons ) }
						label={ __( 'Coupons', 'wc-admin' ) }
						delta={ 15 }
					/>
					<SummaryNumber
						value={ formatCurrency( totals.taxes ) }
						label={ __( 'Taxes', 'wc-admin' ) }
					/>
				</SummaryList>

				<TableCard
					title={ __( 'Revenue Last Week', 'wc-admin' ) }
					rows={ rows }
					headers={ headers }
					onClickDownload={ noop }
					onQueryChange={ this.onQueryChange }
					query={ query }
					summary={ summary }
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

export default RevenueReport;
