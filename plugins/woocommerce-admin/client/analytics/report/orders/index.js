/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { Button } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';
import moment from 'moment';
import { map, partial } from 'lodash';

/**
 * Internal dependencies
 */
import {
	Card,
	ReportFilters,
	SummaryList,
	SummaryListPlaceholder,
	SummaryNumber,
} from '@woocommerce/components';
import { filters, advancedFilterConfig } from './config';
import { formatCurrency } from 'lib/currency';
import { getNewPath } from 'lib/nav-utils';
import { getReportChartData } from 'store/reports/utils';
import { getCurrentDates, getDateParamsFromQuery, getIntervalForQuery } from 'lib/date';
import { MAX_PER_PAGE } from 'store/constants';

class OrdersReport extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			primaryTotals: null,
			secondaryTotals: null,
		};

		this.toggleStatus = this.toggleStatus.bind( this );
	}

	getCharts() {
		return [
			{
				key: 'net_revenue',
				label: __( 'Net Revenue', 'wc-admin' ),
				type: 'currency',
			},
			{
				key: 'avg_order_value',
				label: __( 'Avergae Order Value', 'wc-admin' ),
				type: 'currency',
			},
			{
				key: 'avg_items_per_order',
				label: __( 'Average Items Per Order', 'wc-admin' ),
				type: 'number',
			},
			{
				key: 'Orders Count',
				label: __( 'Orders Count', 'wc-admin' ),
				type: 'number',
			},
		];
	}

	getSelectedChart() {
		const { query } = this.props;
		const charts = this.getCharts();

		const chart = find( charts, { key: query.chart } );
		if ( chart ) {
			return chart;
		}

		return charts[ 0 ];
	}

	toggleStatus( order ) {
		const { requestUpdateOrder } = this.props;
		const updatedOrder = { ...order };
		const status = updatedOrder.status === 'completed' ? 'processing' : 'completed';
		updatedOrder.status = status;
		requestUpdateOrder( updatedOrder );
	}

	renderChartSummaryNumbers() {
		const selectedChart = this.getSelectedChart();
		const charts = this.getCharts();
		if ( ! this.state.primaryTotals || ! this.state.secondaryTotals ) {
			return <SummaryListPlaceholder numberOfItems={ charts.length } />;
		}

		const totals = this.state.primaryTotals || {};
		const secondaryTotals = this.state.secondaryTotals || {};
		const { compare } = getDateParamsFromQuery( this.props.query );

		const summaryNumbers = map( this.getCharts(), chart => {
			const { key, label, type } = chart;
			const isSelected = selectedChart.key === key;

			let value = parseFloat( totals[ key ] );
			let secondaryValue =
				( secondaryTotals[ key ] && parseFloat( secondaryTotals[ key ] ) ) || undefined;

			let delta = 0;
			if ( secondaryValue && secondaryValue !== 0 ) {
				delta = Math.round( ( value - secondaryValue ) / secondaryValue * 100 );
			}

			switch ( type ) {
				// TODO: implement other format handlers
				case 'currency':
					value = formatCurrency( value );
					secondaryValue = secondaryValue && formatCurrency( secondaryValue );
					break;
				case 'number':
					value = value;
					secondaryValue = secondaryValue;
					break;
			}

			const href = getNewPath( { chart: key } );

			return (
				<SummaryNumber
					key={ key }
					value={ value }
					label={ label }
					selected={ isSelected }
					prevValue={ secondaryValue }
					prevLabel={
						'previous_period' === compare
							? __( 'Previous Period:', 'wc-admin' )
							: __( 'Previous Year:', 'wc-admin' )
					}
					delta={ delta }
					href={ href }
				/>
			);
		} );

		return <SummaryList>{ summaryNumbers }</SummaryList>;
	}

	render() {
		const { orders, orderIds, query, path } = this.props;
		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					advancedConfig={ advancedFilterConfig }
				/>
				{ this.renderChartSummaryNumbers() }
				<p>Below is a temporary example</p>
				<Card title="Orders">
					<table style={ { width: '100%' } }>
						<thead>
							<tr style={ { textAlign: 'left' } }>
								<th>Id</th>
								<th>Date</th>
								<th>Total</th>
								<th>Status</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							{ orderIds &&
								orderIds.map( id => {
									const order = orders[ id ];
									return (
										<tr key={ id }>
											<td>{ id }</td>
											<td>{ moment( order.date_created ).format( 'LL' ) }</td>
											<td>{ order.total }</td>
											<td>{ order.status }</td>
											<td>
												<Button isPrimary onClick={ partial( this.toggleStatus, order ) }>
													Toggle Status
												</Button>
											</td>
										</tr>
									);
								} ) }
						</tbody>
					</table>
				</Card>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { getOrders, getOrderIds } = select( 'wc-admin' );
		const { query } = props;
		const interval = getIntervalForQuery( query );
		const datesFromQuery = getCurrentDates( query );
		const baseArgs = {
			order: 'asc',
			interval: interval,
			per_page: MAX_PER_PAGE,
		};

		const primaryData = getReportChartData(
			'orders',
			{
				...baseArgs,
				after: datesFromQuery.primary.after,
				before: datesFromQuery.primary.before,
			},
			select
		);

		const secondaryData = getReportChartData(
			'orders',
			{
				...baseArgs,
				after: datesFromQuery.secondary.after,
				before: datesFromQuery.secondary.before,
			},
			select
		);
		return {
			orders: getOrders(),
			orderIds: getOrderIds(),
			primaryData,
			secondaryData,
		};
	} ),
	withDispatch( dispatch => {
		return {
			requestUpdateOrder: function( updatedOrder ) {
				dispatch( 'wc-admin' ).requestUpdateOrder( updatedOrder );
			},
		};
	} )
)( OrdersReport );
