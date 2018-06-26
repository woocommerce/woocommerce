/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { get, map } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import DatePicker from 'components/date-picker';
import { getAdminLink, updateQueryString } from 'lib/nav-utils';
import { getCurrencyFormatString } from 'lib/currency';
import { getReportData } from 'lib/swagger';
import Header from 'components/header';
import { SummaryList, SummaryNumber } from 'components/summary';
import Table from 'components/table';
import Pagination from 'components/pagination';

// Mock data until we fetch from an API
import rawData from './mock-data';

class RevenueReport extends Component {
	constructor() {
		super();
		this.onPageChange = this.onPageChange.bind( this );
		this.onPerPageChange = this.onPerPageChange.bind( this );

		// TODO remove this when we implement real endpoints
		this.state = { stats: {} };
	}

	onPageChange( page ) {
		updateQueryString( { page } );
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

			response.json().then( data => {
				this.setState( { stats: data } );
			} );
		} );
	}

	onPerPageChange( perPage ) {
		updateQueryString( { per_page: perPage } );
	}

	getRowsContent( data ) {
		return map( data, ( row, label ) => {
			// @TODO How to create this per-report? Can use `w`, `year`, `m` to build time-specific order links
			// we need to know which kind of report this is, and parse the `label` to get this row's date
			const orderLink = (
				<a href={ getAdminLink( '/edit.php?post_type=shop_order&w=4&year=2018' ) }>
					{ row.order_count }
				</a>
			);
			return [
				label,
				orderLink,
				getCurrencyFormatString( row.gross_revenue ),
				getCurrencyFormatString( row.refunds ),
				getCurrencyFormatString( row.coupons ),
				getCurrencyFormatString( row.taxes ),
				getCurrencyFormatString( row.shipping ),
				getCurrencyFormatString( row.net_revenue ),
			];
		} );
	}

	render() {
		const { path, query } = this.props;
		const rows = this.getRowsContent( rawData.intervals[ 0 ].week[ 0 ] );
		const headers = [
			__( 'Date', 'woo-dash' ),
			__( 'Orders', 'woo-dash' ),
			__( 'Gross Revenue', 'woo-dash' ),
			__( 'Refunds', 'woo-dash' ),
			__( 'Coupons', 'woo-dash' ),
			__( 'Taxes', 'woo-dash' ),
			__( 'Shipping', 'woo-dash' ),
			__( 'Net Revenue', 'woo-dash' ),
		];
		const summaryStats = get( this.state.stats, 'totals', {} );

		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics', __( 'Analytics', 'woo-dash' ) ],
						__( 'Revenue', 'woo-dash' ),
					] }
				/>
				<DatePicker query={ query } path={ path } />

				<SummaryList>
					<SummaryNumber
						value={ summaryStats.gross_revenue }
						label={ __( 'Gross Revenue', 'woo-dash' ) }
						delta={ 29 }
					/>
					<SummaryNumber
						value={ summaryStats.refunds }
						label={ __( 'Refunds', 'woo-dash' ) }
						delta={ -10 }
						selected
					/>
					<SummaryNumber
						value={ summaryStats.coupons }
						label={ __( 'Coupons', 'woo-dash' ) }
						delta={ 15 }
					/>
					<SummaryNumber value={ summaryStats.taxes } label={ __( 'Taxes', 'woo-dash' ) } />
				</SummaryList>

				<Card title={ __( 'Gross Revenue' ) }>
					<p>Graph here</p>
					<hr />
					<Table rows={ rows } headers={ headers } caption={ __( 'Revenue Last Week' ) } />
				</Card>

				<Pagination
					page={ parseInt( query.page ) || 1 }
					perPage={ parseInt( query.per_page ) || 25 }
					total={ 5000 }
					onPageChange={ this.onPageChange }
					onPerPageChange={ this.onPerPageChange }
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
