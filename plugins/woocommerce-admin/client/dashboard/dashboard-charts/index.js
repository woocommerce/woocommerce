/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import { isEqual, xor } from 'lodash';
import PropTypes from 'prop-types';
import { ToggleControl, IconButton, NavigableMenu, SelectControl } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { EllipsisMenu, MenuItem, SectionHeader } from '@woocommerce/components';
import { getAllowedIntervalsForQuery } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import ChartBlock from './block';
import { getChartFromKey, uniqCharts } from './config';
import withSelect from 'wc-api/with-select';
import './style.scss';

class DashboardCharts extends Component {
	constructor( props ) {
		super( ...arguments );
		this.state = {
			chartType: props.userPrefChartType || 'line',
			hiddenChartKeys: props.userPrefCharts || [],
			interval: props.userPrefIntervals || 'day',
		};

		this.toggle = this.toggle.bind( this );
	}

	componentDidUpdate( {
		userPrefCharts: prevUserPrefCharts,
		userPrefChartType: prevUserPrefChartType,
		userPrefChartInterval: prevUserPrefChartInterval,
	} ) {
		const { userPrefCharts, userPrefChartInterval, userPrefChartType } = this.props;
		if ( userPrefCharts && ! isEqual( userPrefCharts, prevUserPrefCharts ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				hiddenChartKeys: userPrefCharts,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
		if ( userPrefChartType && userPrefChartType !== prevUserPrefChartType ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				chartType: userPrefChartType,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
		if ( userPrefChartInterval !== prevUserPrefChartInterval ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				interval: userPrefChartInterval,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	toggle( key ) {
		return () => {
			const hiddenChartKeys = xor( this.state.hiddenChartKeys, [ key ] );
			this.setState( { hiddenChartKeys } );
			const userDataFields = {
				[ 'dashboard_charts' ]: hiddenChartKeys,
			};
			this.props.updateCurrentUserData( userDataFields );
		};
	}

	handleTypeToggle( chartType ) {
		return () => {
			this.setState( { chartType: chartType } );
			const userDataFields = {
				[ 'dashboard_chart_type' ]: chartType,
			};
			this.props.updateCurrentUserData( userDataFields );
		};
	}

	renderMenu() {
		return (
			<EllipsisMenu label={ __( 'Choose which charts to display', 'wc-admin' ) }>
				{ uniqCharts.map( chart => {
					return (
						<MenuItem onInvoke={ this.toggle( chart.key ) } key={ chart.key }>
							<ToggleControl
								label={ __( `${ chart.label }`, 'wc-admin' ) }
								checked={ ! this.state.hiddenChartKeys.includes( chart.key ) }
								onChange={ this.toggle( chart.key ) }
							/>
						</MenuItem>
					);
				} ) }
			</EllipsisMenu>
		);
	}

	renderIntervalSelector() {
		const allowedIntervals = getAllowedIntervalsForQuery( this.props.query );
		if ( ! allowedIntervals || allowedIntervals.length < 1 ) {
			return null;
		}

		const intervalLabels = {
			hour: __( 'By hour', 'wc-admin' ),
			day: __( 'By day', 'wc-admin' ),
			week: __( 'By week', 'wc-admin' ),
			month: __( 'By month', 'wc-admin' ),
			quarter: __( 'By quarter', 'wc-admin' ),
			year: __( 'By year', 'wc-admin' ),
		};

		return (
			<SelectControl
				className="woocommerce-chart__interval-select"
				value={ this.state.interval }
				options={ allowedIntervals.map( allowedInterval => ( {
					value: allowedInterval,
					label: intervalLabels[ allowedInterval ],
				} ) ) }
				onChange={ this.setInterval }
			/>
		);
	}

	setInterval = interval => {
		this.setState( { interval }, () => {
			const userDataFields = {
				[ 'dashboard_chart_interval' ]: this.state.interval,
			};
			this.props.updateCurrentUserData( userDataFields );
		} );
	};

	render() {
		const { path } = this.props;
		const { chartType, hiddenChartKeys, interval } = this.state;
		const query = { ...this.props.query, chartType: chartType, interval };
		return (
			<Fragment>
				<div className="woocommerce-dashboard__dashboard-charts">
					<SectionHeader
						title={ __( 'Charts', 'wc-admin' ) }
						menu={ this.renderMenu() }
						className={ 'has-interval-select' }
					>
						{ this.renderIntervalSelector() }
						<NavigableMenu
							className="woocommerce-chart__types"
							orientation="horizontal"
							role="menubar"
						>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected':
										! query.chartType || query.chartType === 'line',
								} ) }
								icon={ <Gridicon icon="line-graph" /> }
								title={ __( 'Line chart', 'wc-admin' ) }
								aria-checked={ query.chartType === 'line' }
								role="menuitemradio"
								tabIndex={ query.chartType === 'line' ? 0 : -1 }
								onClick={ this.handleTypeToggle( 'line' ) }
							/>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected': query.chartType === 'bar',
								} ) }
								icon={ <Gridicon icon="stats-alt" /> }
								title={ __( 'Bar chart', 'wc-admin' ) }
								aria-checked={ query.chartType === 'bar' }
								role="menuitemradio"
								tabIndex={ query.chartType === 'bar' ? 0 : -1 }
								onClick={ this.handleTypeToggle( 'bar' ) }
							/>
						</NavigableMenu>
					</SectionHeader>
					<div className="woocommerce-dashboard__columns">
						{ uniqCharts.map( chart => {
							return hiddenChartKeys.includes( chart.key ) ? null : (
								<ChartBlock
									charts={ getChartFromKey( chart.key ) }
									endpoint={ chart.endpoint }
									key={ chart.key }
									path={ path }
									query={ query }
								/>
							);
						} ) }
					</div>
				</div>
			</Fragment>
		);
	}
}

DashboardCharts.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( select => {
		const { getCurrentUserData } = select( 'wc-api' );
		const userData = getCurrentUserData();

		return {
			userPrefCharts: userData.dashboard_charts,
			userPrefChartType: userData.dashboard_chart_type,
			userPrefChartInterval: userData.dashboard_chart_interval,
		};
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( DashboardCharts );
