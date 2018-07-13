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
import { formatCurrency } from 'lib/currency';
import { getAdminLink, updateQueryString } from 'lib/nav-utils';
import { getReportData } from 'lib/swagger';
import Header from 'layout/header';
import { SummaryList, SummaryNumber } from 'components/summary';
import Table from 'components/table';
import Pagination from 'components/pagination';

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
				row.start_date,
				orderLink,
				formatCurrency( gross_revenue ),
				formatCurrency( refunds ),
				formatCurrency( coupons ),
				formatCurrency( taxes ),
				formatCurrency( shipping ),
				formatCurrency( net_revenue ),
			];
		} );
	}

	render() {
		const { path, query } = this.props;
		const summaryStats = get( this.state.stats, 'totals', {} );
		const intervalStats = get( this.state.stats, 'intervals', [] );
		const rows = this.getRowsContent( intervalStats ) || [];

		const headers = [
			__( 'Date', 'wc-admin' ),
			__( 'Orders', 'wc-admin' ),
			__( 'Gross Revenue', 'wc-admin' ),
			__( 'Refunds', 'wc-admin' ),
			__( 'Coupons', 'wc-admin' ),
			__( 'Taxes', 'wc-admin' ),
			__( 'Shipping', 'wc-admin' ),
			__( 'Net Revenue', 'wc-admin' ),
		];

		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics', __( 'Analytics', 'wc-admin' ) ],
						__( 'Revenue', 'wc-admin' ),
					] }
				/>
				<DatePicker query={ query } path={ path } />

				<SummaryList>
					<SummaryNumber
						value={ formatCurrency( summaryStats.gross_revenue ) }
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						delta={ 29 }
					/>
					<SummaryNumber
						value={ formatCurrency( summaryStats.refunds ) }
						label={ __( 'Refunds', 'wc-admin' ) }
						delta={ -10 }
						selected
					/>
					<SummaryNumber
						value={ formatCurrency( summaryStats.coupons ) }
						label={ __( 'Coupons', 'wc-admin' ) }
						delta={ 15 }
					/>
					<SummaryNumber
						value={ formatCurrency( summaryStats.taxes ) }
						label={ __( 'Taxes', 'wc-admin' ) }
					/>
				</SummaryList>

				<Card title={ __( 'Gross Revenue' ) }>
					<p>Graph here</p>
					<hr />
					{ /* @todo Switch a placeholder view if we don't have rows */ }
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
