/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';
import { IconButton, NavigableMenu, SelectControl, TextControl } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { EllipsisMenu, MenuItem, MenuTitle, SectionHeader } from '@woocommerce/components';
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
			interval: props.userPrefChartInterval || 'day',
		};
	}

	handleTypeToggle( chartType ) {
		return () => {
			this.setState( { chartType } );
			const userDataFields = {
				[ 'dashboard_chart_type' ]: chartType,
			};
			this.props.updateCurrentUserData( userDataFields );
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
				label={ __( 'Choose which charts to display', 'woocommerce-admin' ) }
				renderContent={ ( { onToggle } ) => (
					<Fragment>
						<MenuTitle>{ __( 'Charts', 'woocommerce-admin' ) }</MenuTitle>
						{ uniqCharts.map( chart => {
							return (
								<MenuItem
									checked={ ! hiddenBlocks.includes( chart.key ) }
									isCheckbox
									isClickable
									key={ chart.key }
									onInvoke={ () => onToggleHiddenBlock( chart.key )() }
								>
									{ __( `${ chart.label }`, 'woocommerce-admin' ) }
								</MenuItem>
							);
						} ) }
						{ window.wcAdminFeatures[ 'analytics-dashboard/customizable' ] && (
							<Fragment>
								<div className="woocommerce-ellipsis-menu__item">
									<TextControl
										label={ __( 'Section Title', 'woocommerce-admin' ) }
										onBlur={ onTitleBlur }
										onChange={ onTitleChange }
										required
										value={ titleInput }
									/>
								</div>
								<Controls
									onToggle={ onToggle }
									onMove={ onMove }
									onRemove={ onRemove }
									isFirst={ isFirst }
									isLast={ isLast }
								/>
							</Fragment>
						) }
					</Fragment>
				) }
			/>
		);
	}

	renderIntervalSelector() {
		const allowedIntervals = getAllowedIntervalsForQuery( this.props.query );
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
		const { hiddenBlocks, path, title } = this.props;
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
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected':
										! query.chartType || query.chartType === 'line',
								} ) }
								icon={ <Gridicon icon="line-graph" /> }
								title={ __( 'Line chart', 'woocommerce-admin' ) }
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
								title={ __( 'Bar chart', 'woocommerce-admin' ) }
								aria-checked={ query.chartType === 'bar' }
								role="menuitemradio"
								tabIndex={ query.chartType === 'bar' ? 0 : -1 }
								onClick={ this.handleTypeToggle( 'bar' ) }
							/>
						</NavigableMenu>
					</SectionHeader>
					<div className="woocommerce-dashboard__columns">
						{ uniqCharts.map( chart => {
							return hiddenBlocks.includes( chart.key ) ? null : (
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
