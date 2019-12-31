/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { partial, filter, get } from 'lodash';
import { IconButton, Icon, Dropdown, Button } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

/**
 * WooCommerce dependencies
 */
import { H } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import defaultSections from './default-sections';
import Section from './section';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';
import TaskList from './task-list';
import { getAllTasks } from './task-list/tasks';
import { isOnboardingEnabled } from 'dashboard/utils';
import { getCurrentDates, getDateParamsFromQuery, isoDateFormat } from 'lib/date';
import ReportFilters from 'analytics/components/report-filters';

const DASHBOARD_FILTERS_FILTER = 'woocommerce_admin_dashboard_filters';
const filters = applyFilters( DASHBOARD_FILTERS_FILTER, [] );

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
			recordEvent( 'dash_section_rename', { key: updatedKey } );
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

			if ( toggledSection.isVisible ) {
				recordEvent( 'dash_section_add', { key: toggledSection.key } );
			} else {
				recordEvent( 'dash_section_remove', { key: toggledSection.key } );
			}

			this.updateSections( sections );
		};
	}

	onMove( index, change ) {
		const sections = [ ...this.state.sections ];
		const movedSection = sections.splice( index, 1 ).shift();
		const newIndex = index + change;

		// Figure out the index of the skipped section.
		const nextJumpedSectionIndex = change < 0 ? newIndex : newIndex - 1;

		if (
			sections[ nextJumpedSectionIndex ].isVisible || // Is the skipped section visible?
			index === 0 || // Will this be the first element?
			index === sections.length - 1 // Will this be the last element?
		) {
			// Yes, lets insert.
			sections.splice( newIndex, 0, movedSection );
			this.updateSections( sections );

			const eventProps = {
				key: movedSection.key,
				direction: 0 < change ? 'down' : 'up',
			};
			recordEvent( 'dash_section_order_change', eventProps );
		} else {
			// No, lets try the next one.
			this.onMove( index, change + change );
		}
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
		const { query, path, taskListHidden, taskListCompleted } = this.props;
		const { sections } = this.state;
		const visibleSectionKeys = sections
			.filter( section => section.isVisible )
			.map( section => section.key );

		if (
			isOnboardingEnabled() &&
			wcSettings.onboarding &&
			! taskListHidden &&
			( query.task || ! taskListCompleted )
		) {
			return <TaskList query={ query } />;
		}

		const { period, compare, before, after } = getDateParamsFromQuery( query );
		const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates( query );
		const dateQuery = {
			period,
			compare,
			before,
			after,
			primaryDate,
			secondaryDate,
		};

		return (
			<Fragment>
				{ isOnboardingEnabled() &&
					wcSettings.onboarding &&
					! taskListHidden &&
					taskListCompleted && <TaskList query={ query } inline /> }

				<ReportFilters
					report="dashboard"
					query={ query }
					path={ path }
					dateQuery={ dateQuery }
					isoDateFormat={ isoDateFormat }
					filters={ filters }
				/>
				{ sections.map( ( section, index ) => {
					if ( section.isVisible ) {
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
								isFirst={ section.key === visibleSectionKeys[ 0 ] }
								isLast={ section.key === visibleSectionKeys[ visibleSectionKeys.length - 1 ] }
							/>
						);
					}
					return null;
				} ) }
				{ this.renderAddMore() }
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select, props ) => {
		const { getCurrentUserData, getProfileItems, getOptions } = select( 'wc-api' );
		const userData = getCurrentUserData();

		const withSelectData = {
			userPrefSections: userData.dashboard_sections,
		};

		if ( isOnboardingEnabled() ) {
			const profileItems = getProfileItems();
			const tasks = getAllTasks( {
				profileItems,
				options: getOptions( [ 'woocommerce_task_list_payments' ] ),
				query: props.query,
			} );
			const visibleTasks = filter( tasks, task => task.visible );
			const completedTasks = filter( tasks, task => task.visible && task.completed );

			withSelectData.taskListCompleted = visibleTasks.length === completedTasks.length;
			withSelectData.taskListHidden =
				'yes' ===
				get(
					getOptions( [ 'woocommerce_task_list_hidden' ] ),
					[ 'woocommerce_task_list_hidden' ],
					'no'
				);
		}

		return withSelectData;
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( CustomizableDashboard );
