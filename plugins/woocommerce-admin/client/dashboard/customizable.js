/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment, Suspense, lazy } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { partial, get } from 'lodash';
import { IconButton, Icon, Dropdown, Button } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

/**
 * WooCommerce dependencies
 */
import { H, Spinner } from '@woocommerce/components';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';
import defaultSections from './default-sections';
import Section from './section';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';
import { isOnboardingEnabled } from 'dashboard/utils';
import {
	getCurrentDates,
	getDateParamsFromQuery,
	isoDateFormat,
} from 'lib/date';
import ReportFilters from 'analytics/components/report-filters';

const TaskList = lazy( () =>
	import( /* webpackChunkName: "task-list" */ '../task-list' )
);

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
		const defaultKeys = defaultSections.map( ( section ) => section.key );
		const prefKeys = prefSections.map( ( section ) => section.key );
		const keys = new Set( [ ...prefKeys, ...defaultKeys ] );
		const sections = [];

		keys.forEach( ( key ) => {
			const defaultSection = defaultSections.find(
				( section ) => section.key === key
			);
			if ( ! defaultSection ) {
				return;
			}
			const prefSection = prefSections.find(
				( section ) => section.key === key
			);

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
		const newSections = this.state.sections.map( ( section ) => {
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
		return ( updatedHiddenBlocks ) => {
			this.updateSection( updatedKey, {
				hiddenBlocks: updatedHiddenBlocks,
			} );
		};
	}

	onSectionTitleUpdate( updatedKey ) {
		return ( updatedTitle ) => {
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
			const index = sections.findIndex( ( s ) => key === s.key );
			const toggledSection = sections.splice( index, 1 ).shift();
			toggledSection.isVisible = ! toggledSection.isVisible;
			sections.push( toggledSection );

			if ( toggledSection.isVisible ) {
				recordEvent( 'dash_section_add', { key: toggledSection.key } );
			} else {
				recordEvent( 'dash_section_remove', {
					key: toggledSection.key,
				} );
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
				direction: change > 0 ? 'down' : 'up',
			};
			recordEvent( 'dash_section_order_change', eventProps );
		} else {
			// No, lets try the next one.
			this.onMove( index, change + change );
		}
	}

	renderAddMore() {
		const { sections } = this.state;
		const hiddenSections = sections.filter(
			( section ) => section.isVisible === false
		);

		if ( hiddenSections.length === 0 ) {
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
						<H>
							{ __( 'Dashboard Sections', 'woocommerce-admin' ) }
						</H>
						<div className="woocommerce-dashboard-section__add-more-choices">
							{ hiddenSections.map( ( section ) => {
								return (
									<Button
										key={ section.key }
										onClick={ this.toggleVisibility(
											section.key,
											onToggle
										) }
										className="woocommerce-dashboard-section__add-more-btn"
										title={ sprintf(
											__(
												'Add %s section',
												'woocommerce-admin'
											),
											section.title
										) }
									>
										<Icon
											icon={ section.icon }
											size={ 30 }
										/>
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

	renderDashboardReports() {
		const { query, path, defaultDateRange } = this.props;
		const { sections } = this.state;
		const { period, compare, before, after } = getDateParamsFromQuery(
			query,
			defaultDateRange
		);
		const {
			primary: primaryDate,
			secondary: secondaryDate,
		} = getCurrentDates( query, defaultDateRange );
		const dateQuery = {
			period,
			compare,
			before,
			after,
			primaryDate,
			secondaryDate,
		};
		const visibleSectionKeys = sections
			.filter( ( section ) => section.isVisible )
			.map( ( section ) => section.key );

		return (
			<Fragment>
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
								onChangeHiddenBlocks={ this.onChangeHiddenBlocks(
									section.key
								) }
								onTitleUpdate={ this.onSectionTitleUpdate(
									section.key
								) }
								path={ path }
								query={ query }
								title={ section.title }
								onMove={ partial( this.onMove, index ) }
								onRemove={ this.toggleVisibility(
									section.key
								) }
								isFirst={
									section.key === visibleSectionKeys[ 0 ]
								}
								isLast={
									section.key ===
									visibleSectionKeys[
										visibleSectionKeys.length - 1
									]
								}
							/>
						);
					}
					return null;
				} ) }
				{ this.renderAddMore() }
			</Fragment>
		);
	}

	render() {
		const { query, taskListHidden, taskListComplete } = this.props;

		const isTaskListEnabled = isOnboardingEnabled() && ! taskListHidden;

		const isDashboardShown =
			! isTaskListEnabled || ( ! query.task && taskListComplete );

		return (
			<Fragment>
				{ isTaskListEnabled && (
					<Suspense fallback={ <Spinner /> }>
						<TaskList query={ query } inline={ isDashboardShown } />
					</Suspense>
				) }
				{ isDashboardShown && this.renderDashboardReports() }
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getCurrentUserData,
			isGetProfileItemsRequesting,
			getOptions,
			isGetOptionsRequesting,
		} = select( 'wc-api' );
		const userData = getCurrentUserData();
		const { woocommerce_default_date_range: defaultDateRange } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );

		const withSelectData = {
			userPrefSections: userData.dashboard_sections,
			defaultDateRange,
			requesting: false,
		};

		if ( isOnboardingEnabled() ) {
			const options = getOptions( [
				'woocommerce_task_list_complete',
				'woocommerce_task_list_hidden',
			] );
			withSelectData.taskListHidden =
				get( options, [ 'woocommerce_task_list_hidden' ], 'no' ) ===
				'yes';
			withSelectData.taskListComplete = get(
				options,
				[ 'woocommerce_task_list_complete' ],
				false
			);
			withSelectData.requesting =
				withSelectData.requesting || isGetProfileItemsRequesting();
			withSelectData.requesting =
				withSelectData.requesting ||
				isGetOptionsRequesting( [
					'woocommerce_task_list_payments',
					'woocommerce_task_list_hidden',
				] );
		}

		return withSelectData;
	} ),
	withDispatch( ( dispatch ) => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( CustomizableDashboard );
