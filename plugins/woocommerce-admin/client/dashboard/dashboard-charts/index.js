/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';
import { Button, NavigableMenu, SelectControl } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import {
	EllipsisMenu,
	MenuItem,
	MenuTitle,
	SectionHeader,
} from '@woocommerce/components';
import { getAllowedIntervalsForQuery } from 'lib/date';

/**
 * Internal dependencies
 */
import ChartBlock from './block';
import { uniqCharts } from './config';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';
import './style.scss';

class DashboardCharts extends Component {
	constructor( props ) {
		super( ...arguments );

		this.state = {
			chartType: props.userPrefChartType || 'line',
			interval: props.userPrefChartInterval || 'day',
		};
	}

	handleTypeToggle( chartType ) {
		return () => {
			this.setState( { chartType } );
			const userDataFields = {
				dashboard_chart_type: chartType,
			};
			this.props.updateCurrentUserData( userDataFields );
			recordEvent( 'dash_charts_type_toggle', { chart_type: chartType } );
		};
	}

	renderMenu() {
		const {
			hiddenBlocks,
			isFirst,
			isLast,
			onMove,
			onRemove,
			onTitleBlur,
			onTitleChange,
			onToggleHiddenBlock,
			titleInput,
			controls: Controls,
		} = this.props;

		return (
			<EllipsisMenu
				label={ __(
					'Choose which charts to display',
					'woocommerce-admin'
				) }
				renderContent={ ( { onToggle } ) => (
					<Fragment>
						<MenuTitle>
							{ __( 'Charts', 'woocommerce-admin' ) }
						</MenuTitle>
						{ uniqCharts.map( ( chart ) => {
							const key = chart.endpoint + '_' + chart.key;
							const checked = ! hiddenBlocks.includes( key );
							return (
								<MenuItem
									checked={ checked }
									isCheckbox
									isClickable
									key={ chart.endpoint + '_' + chart.key }
									onInvoke={ () => {
										onToggleHiddenBlock( key )();
										recordEvent(
											'dash_charts_chart_toggle',
											{
												status: checked ? 'off' : 'on',
												key,
											}
										);
									} }
								>
									{ chart.label }
								</MenuItem>
							);
						} ) }
						{ window.wcAdminFeatures[
							'analytics-dashboard/customizable'
						] && (
							<Controls
								onToggle={ onToggle }
								onMove={ onMove }
								onRemove={ onRemove }
								isFirst={ isFirst }
								isLast={ isLast }
								onTitleBlur={ onTitleBlur }
								onTitleChange={ onTitleChange }
								titleInput={ titleInput }
							/>
						) }
					</Fragment>
				) }
			/>
		);
	}

	renderIntervalSelector() {
		const allowedIntervals = getAllowedIntervalsForQuery(
			this.props.query
		);
		if ( ! allowedIntervals || allowedIntervals.length < 1 ) {
			return null;
		}

		const intervalLabels = {
			hour: __( 'By hour', 'woocommerce-admin' ),
			day: __( 'By day', 'woocommerce-admin' ),
			week: __( 'By week', 'woocommerce-admin' ),
			month: __( 'By month', 'woocommerce-admin' ),
			quarter: __( 'By quarter', 'woocommerce-admin' ),
			year: __( 'By year', 'woocommerce-admin' ),
		};

		return (
			<SelectControl
				className="woocommerce-chart__interval-select"
				value={ this.state.interval }
				options={ allowedIntervals.map( ( allowedInterval ) => ( {
					value: allowedInterval,
					label: intervalLabels[ allowedInterval ],
				} ) ) }
				onChange={ this.setInterval }
			/>
		);
	}

	setInterval = ( interval ) => {
		this.setState( { interval }, () => {
			const userDataFields = {
				dashboard_chart_interval: this.state.interval,
			};
			this.props.updateCurrentUserData( userDataFields );
			recordEvent( 'dash_charts_interval', { interval } );
		} );
	};

	renderChartBlocks( query ) {
		const { hiddenBlocks, path } = this.props;

		// Reduce the API response to only the necessary stat fields
		// by supplying all charts common to each endpoint.
		const chartsByEndpoint = uniqCharts.reduce( ( byEndpoint, chart ) => {
			if ( typeof byEndpoint[ chart.endpoint ] === 'undefined' ) {
				byEndpoint[ chart.endpoint ] = [];
			}
			byEndpoint[ chart.endpoint ].push( chart );

			return byEndpoint;
		}, {} );

		return (
			<div className="woocommerce-dashboard__columns">
				{ uniqCharts.map( ( chart ) => {
					return hiddenBlocks.includes(
						chart.endpoint + '_' + chart.key
					) ? null : (
						<ChartBlock
							charts={ chartsByEndpoint[ chart.endpoint ] }
							endpoint={ chart.endpoint }
							key={ chart.endpoint + '_' + chart.key }
							path={ path }
							query={ query }
							selectedChart={ chart }
						/>
					);
				} ) }
			</div>
		);
	}

	render() {
		const { title } = this.props;
		const { chartType, interval } = this.state;
		const query = { ...this.props.query, chartType, interval };
		return (
			<Fragment>
				<div className="woocommerce-dashboard__dashboard-charts">
					<SectionHeader
						title={ title || __( 'Charts', 'woocommerce-admin' ) }
						menu={ this.renderMenu() }
						className={ 'has-interval-select' }
					>
						{ this.renderIntervalSelector() }
						<NavigableMenu
							className="woocommerce-chart__types"
							orientation="horizontal"
							role="menubar"
						>
							<Button
								className={ classNames(
									'woocommerce-chart__type-button',
									{
										'woocommerce-chart__type-button-selected':
											! query.chartType ||
											query.chartType === 'line',
									}
								) }
								title={ __(
									'Line chart',
									'woocommerce-admin'
								) }
								aria-checked={ query.chartType === 'line' }
								role="menuitemradio"
								tabIndex={ query.chartType === 'line' ? 0 : -1 }
								onClick={ this.handleTypeToggle( 'line' ) }
							>
								<Gridicon icon="line-graph" />
							</Button>
							<Button
								className={ classNames(
									'woocommerce-chart__type-button',
									{
										'woocommerce-chart__type-button-selected':
											query.chartType === 'bar',
									}
								) }
								title={ __( 'Bar chart', 'woocommerce-admin' ) }
								aria-checked={ query.chartType === 'bar' }
								role="menuitemradio"
								tabIndex={ query.chartType === 'bar' ? 0 : -1 }
								onClick={ this.handleTypeToggle( 'bar' ) }
							>
								<Gridicon icon="stats-alt" />
							</Button>
						</NavigableMenu>
					</SectionHeader>
					{ this.renderChartBlocks( query ) }
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
	withSelect( ( select ) => {
		const { getCurrentUserData } = select( 'wc-api' );
		const userData = getCurrentUserData();

		return {
			userPrefChartType: userData.dashboard_chart_type,
			userPrefChartInterval: userData.dashboard_chart_interval,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( DashboardCharts );
