/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import Gridicon from 'gridicons';
import { ToggleControl, IconButton, NavigableMenu } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { EllipsisMenu, MenuItem, SectionHeader } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import ChartBlock from './block';
import { getChartFromKey, showCharts } from './config';
import './style.scss';

class DashboardCharts extends Component {
	constructor( props ) {
		super( ...arguments );
		this.state = {
			showCharts,
			query: props.query,
		};

		this.toggle = this.toggle.bind( this );
	}

	toggle( key ) {
		return () => {
			this.setState( state => {
				const foundIndex = state.showCharts.findIndex( x => x.key === key );
				state.showCharts[ foundIndex ].show = ! state.showCharts[ foundIndex ].show;
				return state;
			} );
		};
	}

	handleTypeToggle( type ) {
		return () => {
			this.setState( state => ( { query: { ...state.query, type } } ) );
		};
	}

	renderMenu() {
		return (
			<EllipsisMenu label={ __( 'Choose which charts to display', 'wc-admin' ) }>
				{ this.state.showCharts.map( chart => {
					return (
						<MenuItem onInvoke={ this.toggle( chart.key ) } key={ chart.key }>
							<ToggleControl
								label={ __( `${ chart.label }`, 'wc-admin' ) }
								checked={ chart.show }
								onChange={ this.toggle( chart.key ) }
							/>
						</MenuItem>
					);
				} ) }
			</EllipsisMenu>
		);
	}

	render() {
		const { path } = this.props;
		const { query } = this.state;
		return (
			<Fragment>
				<div className="woocommerce-dashboard__dashboard-charts">
					<SectionHeader title={ __( 'Charts', 'wc-admin' ) } menu={ this.renderMenu() }>
						<NavigableMenu
							className="woocommerce-chart__types"
							orientation="horizontal"
							role="menubar"
						>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected': query.type === 'line',
								} ) }
								icon={ <Gridicon icon="line-graph" /> }
								title={ __( 'Line chart', 'wc-admin' ) }
								aria-checked={ query.type === 'line' }
								role="menuitemradio"
								tabIndex={ query.type === 'line' ? 0 : -1 }
								onClick={ this.handleTypeToggle( 'line' ) }
							/>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected': query.type === 'bar',
								} ) }
								icon={ <Gridicon icon="stats-alt" /> }
								title={ __( 'Bar chart', 'wc-admin' ) }
								aria-checked={ query.type === 'bar' }
								role="menuitemradio"
								tabIndex={ query.type === 'bar' ? 0 : -1 }
								onClick={ this.handleTypeToggle( 'bar' ) }
							/>
						</NavigableMenu>
					</SectionHeader>
					<div className="woocommerce-dashboard__columns">
						{ this.state.showCharts.map( chart => {
							return ! chart.show ? null : (
								<div key={ chart.key }>
									<ChartBlock
										charts={ getChartFromKey( chart.key ) }
										endpoint={ chart.endpoint }
										path={ path }
										query={ query }
									/>
								</div>
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

export default DashboardCharts;
