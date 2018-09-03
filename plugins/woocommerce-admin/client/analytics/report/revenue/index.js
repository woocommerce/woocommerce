/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { format as formatDate } from '@wordpress/date';
import { map } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import {
	Card,
	Chart,
	ReportFilters,
	SummaryList,
	SummaryNumber,
	TableCard,
} from '@woocommerce/components';
import { downloadCSVFile, generateCSVDataFromTable, generateCSVFileName } from 'lib/csv';
import { formatCurrency, getCurrencyFormatDecimal } from 'lib/currency';
import { getAdminLink, getNewPath, updateQueryString } from 'lib/nav-utils';
import { getReportData } from 'lib/swagger';

// Mock data until we fetch from an API
import mockData from './__mocks__/mock-data';
import testData from './data';
const charts = {
	gross_revenue: {
		label: __( 'Gross Revenue', 'wc-admin' ),
		type: 'currency',
	},
	refunds: {
		label: __( 'Refunds', 'wc-admin' ),
		type: 'currency',
	},
	coupons: {
		label: __( 'Coupons', 'wc-admin' ),
		type: 'currency',
	},
	taxes: {
		label: __( 'Taxes', 'wc-admin' ),
		type: 'currency',
	},
};

class RevenueReport extends Component {
	constructor() {
		super();
		this.onDownload = this.onDownload.bind( this );
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
				this.setState( { stats: mockData } );
			} );
		} );
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

	// TODO since this pattern will exist on every report, this possibly should become a component
	getChartSummaryNumbers() {
		const { totals = {} } = this.state.stats;
		const selectedChart = this.getSelectedChart();

		const summaryNumbers = map( charts, ( chart, key ) => {
			const { label, type } = chart;
			const isSelected = selectedChart === key;
			let value = totals[ key ];

			switch ( type ) {
				// TODO: implement other format handlers
				case 'currency':
					value = formatCurrency( value );
					break;
			}

			const href = getNewPath( { chart: key } );

			return (
				<SummaryNumber
					key={ key }
					value={ value }
					label={ label }
					selected={ isSelected }
					delta={ 0 }
					href={ href }
				/>
			);
		} );

		return <SummaryList>{ summaryNumbers }</SummaryList>;
	}

	getSelectedChart() {
		const { query } = this.props;
		const { chart } = query;
		if ( chart && charts[ chart ] ) {
			return chart;
		}

		return 'gross_revenue';
	}

	render() {
		const { path, query } = this.props;
		const { totals = {}, intervals = [] } = this.state.stats;
		const summary = this.getSummaryContent( totals ) || [];
		const rows = this.getRowsContent( intervals ) || [];
		const headers = this.getHeadersContent();
		const selectedChart = this.getSelectedChart();

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } />

				{ this.getChartSummaryNumbers() }
				<Card title="">
					<Chart data={ testData[ selectedChart ] } title={ charts[ selectedChart ].label } />
				</Card>

				<TableCard
					title={ __( 'Revenue Last Week', 'wc-admin' ) }
					rows={ rows }
					headers={ headers }
					onClickDownload={ this.onDownload( headers, rows, query ) }
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
