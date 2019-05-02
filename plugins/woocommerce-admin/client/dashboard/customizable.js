/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './style.scss';
import DashboardCharts from './dashboard-charts';
import Leaderboards from './leaderboards';
import Section from './section';
import { ReportFilters, H } from '@woocommerce/components';
import StorePerformance from './store-performance';

// @todo Replace dashboard-charts, leaderboards, and store-performance sections as neccessary with customizable equivalents.
export default class CustomizableDashboard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			sections: applyFilters( 'woocommerce_dashboard_sections', [
				{
					key: 'store-performance',
					component: StorePerformance,
					title: __( 'Store Performance', 'woocommerce-admin' ),
				},
				{
					key: 'charts',
					component: DashboardCharts,
					title: __( 'Charts', 'woocommerce-admin' ),
				},
				{
					key: 'leaderboards',
					component: Leaderboards,
					title: __( 'Leaderboards', 'woocommerce-admin' ),
				},
			] ),
		};
	}

	onSectionTitleUpdate( updatedKey ) {
		return updatedTitle => {
			this.setState( {
				sections: this.state.sections.map( section => {
					if ( section.key === updatedKey ) {
						return {
							...section,
							title: updatedTitle,
						};
					}
					return section;
				} ),
			} );
		};
	}

	render() {
		const { query, path } = this.props;
		const { sections } = this.state;

		return (
			<Fragment>
				<H>{ __( 'Customizable Dashboard', 'woocommerce-admin' ) }</H>
				<ReportFilters query={ query } path={ path } />
				{ sections.map( section => {
					return (
						<Section
							component={ section.component }
							key={ section.key }
							onTitleUpdate={ this.onSectionTitleUpdate( section.key ) }
							path={ path }
							query={ query }
							title={ section.title }
						/>
					);
				} ) }
			</Fragment>
		);
	}
}
