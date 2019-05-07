/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { partial } from 'lodash';
import { IconButton, Icon, Dropdown, Button } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { H, ReportFilters } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import defaultSections from './default-sections';
import Section from './components/section';
import withSelect from 'wc-api/with-select';

class CustomizableDashboard extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			sections: this.mergeSectionsWithDefaults( props.userPrefSections ),
		};

		this.onMove = this.onMove.bind( this );
		this.updateSection = this.updateSection.bind( this );
	}

	mergeSectionsWithDefaults( prefSections ) {
		if ( ! prefSections || prefSections.length === 0 ) {
			return defaultSections;
		}
		const defaultKeys = defaultSections.map( section => section.key );
		const prefKeys = prefSections.map( section => section.key );
		const keys = new Set( [ ...prefKeys, ...defaultKeys ] );
		const sections = [];

		keys.forEach( key => {
			const defaultSection = defaultSections.find( section => section.key === key );
			if ( ! defaultSection ) {
				return;
			}
			const prefSection = prefSections.find( section => section.key === key );

			sections.push( {
				...defaultSection,
				...prefSection,
			} );
		} );

		return sections;
	}

	updateSections( newSections ) {
		this.setState( { sections: newSections } );
		this.props.updateCurrentUserData( { dashboard_sections: newSections } );
	}

	updateSection( updatedKey, newSettings ) {
		const newSections = this.state.sections.map( section => {
			if ( section.key === updatedKey ) {
				return {
					...section,
					...newSettings,
				};
			}
			return section;
		} );
		this.updateSections( newSections );
	}

	onChangeHiddenBlocks( updatedKey ) {
		return updatedHiddenBlocks => {
			this.updateSection( updatedKey, { hiddenBlocks: updatedHiddenBlocks } );
		};
	}

	onSectionTitleUpdate( updatedKey ) {
		return updatedTitle => {
			this.updateSection( updatedKey, { title: updatedTitle } );
		};
	}

	toggleVisibility( key, onToggle ) {
		return () => {
			if ( onToggle ) {
				// Close the dropdown before setting state so an action is not performed on an unmounted component.
				onToggle();
			}
			// When toggling visibility, place section at the end of the array.
			const sections = [ ...this.state.sections ];
			const index = sections.findIndex( s => key === s.key );
			const toggledSection = sections.splice( index, 1 ).shift();
			toggledSection.isVisible = ! toggledSection.isVisible;
			sections.push( toggledSection );

			this.updateSections( sections );
		};
	}

	onMove( index, change ) {
		const sections = [ ...this.state.sections ];
		const movedSection = sections.splice( index, 1 ).shift();
		sections.splice( index + change, 0, movedSection );

		this.updateSections( sections );
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
							hiddenBlocks={ section.hiddenBlocks }
							key={ section.key }
							onChangeHiddenBlocks={ this.onChangeHiddenBlocks( section.key ) }
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

export default compose(
	withSelect( select => {
		const { getCurrentUserData } = select( 'wc-api' );
		const userData = getCurrentUserData();

		return {
			userPrefSections: userData.dashboard_sections,
		};
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( CustomizableDashboard );
