/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { partial } from 'lodash';
import { IconButton, Icon, Dropdown, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';
import DashboardCharts from './dashboard-charts';
import Leaderboards from './leaderboards';
import Section from './section';
import { ReportFilters, H } from '@woocommerce/components';
import StorePerformance from './store-performance';

export default class CustomizableDashboard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			sections: applyFilters( 'woocommerce_dashboard_sections', [
				{
					key: 'store-performance',
					component: StorePerformance,
					title: __( 'Performance', 'woocommerce-admin' ),
					isVisible: true,
					icon: 'arrow-right-alt',
				},
				{
					key: 'charts',
					component: DashboardCharts,
					title: __( 'Charts', 'woocommerce-admin' ),
					isVisible: true,
					icon: 'chart-bar',
				},
				{
					key: 'leaderboards',
					component: Leaderboards,
					title: __( 'Leaderboards', 'woocommerce-admin' ),
					isVisible: true,
					icon: 'editor-ol',
				},
			] ),
		};

		this.onMove = this.onMove.bind( this );
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

	toggleVisibility( key, onToggle ) {
		return () => {
			if ( onToggle ) {
				// Close the dropdown before setting state so an action is not performed on an unmounted component.
				onToggle();
			}
			this.setState( state => {
				// When toggling visibility, place section at the end of the array.
				const sections = [ ...state.sections ];
				const index = sections.findIndex( s => key === s.key );
				const toggledSection = sections.splice( index, 1 ).shift();
				toggledSection.isVisible = ! toggledSection.isVisible;
				sections.push( toggledSection );

				return { sections };
			} );
		};
	}

	onMove( index, change ) {
		const sections = [ ...this.state.sections ];
		const movedSection = sections.splice( index, 1 ).shift();
		sections.splice( index + change, 0, movedSection );

		this.setState( { sections } );
	}

	renderAddMore() {
		const { sections } = this.state;
		const hiddenSections = sections.filter( section => false === section.isVisible );

		if ( 0 === hiddenSections.length ) {
			return null;
		}

		return (
			<Dropdown
				position="top center"
				className="woocommerce-dashboard-section__add-more"
				renderToggle={ ( { onToggle, isOpen } ) => (
					<IconButton
						onClick={ onToggle }
						icon="plus-alt"
						title={ __( 'Add more sections', 'woocommerce-admin' ) }
						aria-expanded={ isOpen }
					/>
				) }
				renderContent={ ( { onToggle } ) => (
					<Fragment>
						<H>{ __( 'Dashboard Sections', 'woocommerce-admin' ) }</H>
						<div className="woocommerce-dashboard-section__add-more-choices">
							{ hiddenSections.map( section => {
								return (
									<Button
										key={ section.key }
										onClick={ this.toggleVisibility( section.key, onToggle ) }
										className="woocommerce-dashboard-section__add-more-btn"
										title={ sprintf( __( 'Add %s section', 'woocommerce-admin' ), section.title ) }
									>
										<Icon icon={ section.icon } size={ 30 } />
										<span className="woocommerce-dashboard-section__add-more-btn-title">
											{ section.title }
										</span>
									</Button>
								);
							} ) }
						</div>
					</Fragment>
				) }
			/>
		);
	}

	render() {
		const { query, path } = this.props;
		const { sections } = this.state;
		const visibleSections = sections.filter( section => section.isVisible );

		return (
			<Fragment>
				<H>{ __( 'Customizable Dashboard', 'woocommerce-admin' ) }</H>
				<ReportFilters query={ query } path={ path } />
				{ visibleSections.map( ( section, index ) => {
					return (
						<Section
							component={ section.component }
							key={ section.key }
							onTitleUpdate={ this.onSectionTitleUpdate( section.key ) }
							path={ path }
							query={ query }
							title={ section.title }
							onMove={ partial( this.onMove, index ) }
							onRemove={ this.toggleVisibility( section.key ) }
							isFirst={ 0 === index }
							isLast={ visibleSections.length === index + 1 }
						/>
					);
				} ) }
				{ this.renderAddMore() }
			</Fragment>
		);
	}
}
