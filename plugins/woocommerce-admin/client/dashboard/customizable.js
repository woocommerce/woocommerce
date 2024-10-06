/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { partial } from 'lodash';
import { Dropdown, Button } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { Icon, plusCircleFilled } from '@wordpress/icons';
import { withSelect } from '@wordpress/data';
import { H } from '@woocommerce/components';
import { SETTINGS_STORE_NAME, useUserPreferences } from '@woocommerce/data';
import { getQuery } from '@woocommerce/navigation';
import {
	getCurrentDates,
	getDateParamsFromQuery,
	isoDateFormat,
} from '@woocommerce/date';
import { recordEvent } from '@woocommerce/tracks';
import {
	CurrencyContext,
	getFilteredCurrencyInstance,
} from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import './style.scss';
import defaultSections, { DEFAULT_SECTIONS_FILTER } from './default-sections';
import Section from './section';
import ReportFilters from '../analytics/components/report-filters';

const DASHBOARD_FILTERS_FILTER = 'woocommerce_admin_dashboard_filters';

/**
 * @typedef {import('../analytics/report/index.js').filter} filter
 */

/**
 * Add Report filters to the dashboard. None are added by default.
 *
 * @filter woocommerce_admin_dashboard_filters
 * @param {Array.<filter>} filters Report filters.
 */
const filters = applyFilters( DASHBOARD_FILTERS_FILTER, [] );

const mergeSectionsWithDefaults = ( prefSections ) => {
	if (
		! prefSections ||
		! Array.isArray( prefSections ) ||
		prefSections.length === 0
	) {
		return defaultSections.reduce( ( sections, section ) => {
			return [ ...sections, { ...section } ];
		}, [] );
	}

	if ( ! Array.isArray( defaultSections ) ) {
		throw new Error(
			`The \`defaultSections\` is not an array, please make sure \`${ DEFAULT_SECTIONS_FILTER }\` filter is used correctly.`
		);
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
		// Not defined by a string anymore.
		if ( prefSection ) {
			delete prefSection.icon;
		}

		sections.push( {
			...defaultSection,
			...prefSection,
		} );
	} );

	return sections;
};

const CustomizableDashboard = ( { defaultDateRange, path, query } ) => {
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();

	const sections = useMemo(
		() => mergeSectionsWithDefaults( userPrefs.dashboard_sections ),
		[ userPrefs.dashboard_sections ]
	);

	const updateSections = ( newSections ) => {
		updateUserPreferences( { dashboard_sections: newSections } );
	};

	const updateSection = ( updatedKey, newSettings ) => {
		const newSections = sections.map( ( section ) => {
			// Do not save section icon as it is a component.
			delete section.icon;
			if ( section.key === updatedKey ) {
				return {
					...section,
					...newSettings,
				};
			}

			return section;
		} );
		updateSections( newSections );
	};

	const onChangeHiddenBlocks = ( updatedKey ) => {
		return ( updatedHiddenBlocks ) => {
			updateSection( updatedKey, {
				hiddenBlocks: updatedHiddenBlocks,
			} );
		};
	};

	const onSectionTitleUpdate = ( updatedKey ) => {
		return ( updatedTitle ) => {
			recordEvent( 'dash_section_rename', { key: updatedKey } );
			updateSection( updatedKey, { title: updatedTitle } );
		};
	};

	const toggleVisibility = ( key, onToggle ) => {
		return () => {
			if ( onToggle ) {
				// Close the dropdown before setting state so an action is not performed on an unmounted component.
				onToggle();
			}
			// When toggling visibility, place section at the end of the array.
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

			updateSections( sections );
		};
	};

	const onMove = ( index, change ) => {
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
			updateSections( sections );

			const eventProps = {
				key: movedSection.key,
				direction: change > 0 ? 'down' : 'up',
			};
			recordEvent( 'dash_section_order_change', eventProps );
		} else {
			// No, lets try the next one.
			onMove( index, change + change );
		}
	};

	const renderAddMore = () => {
		const hiddenSections = sections.filter(
			( section ) => section.isVisible === false
		);

		if ( hiddenSections.length === 0 ) {
			return null;
		}

		return (
			<Dropdown
				popoverProps={ {
					placement: 'top',
				} }
				className="woocommerce-dashboard-section__add-more"
				renderToggle={ ( { onToggle, isOpen } ) => (
					<Button
						onClick={ onToggle }
						title={ __( 'Add more sections', 'woocommerce' ) }
						aria-expanded={ isOpen }
					>
						<Icon icon={ plusCircleFilled } />
					</Button>
				) }
				renderContent={ ( { onToggle } ) => (
					<>
						<H>{ __( 'Dashboard Sections', 'woocommerce' ) }</H>
						<div className="woocommerce-dashboard-section__add-more-choices">
							{ hiddenSections.map( ( section ) => {
								return (
									<Button
										key={ section.key }
										onClick={ toggleVisibility(
											section.key,
											onToggle
										) }
										className="woocommerce-dashboard-section__add-more-btn"
										title={ sprintf(
											/* translators: %s: dashboard section titles which are hidden, this button allows unhiding them */
											__(
												'Add %s section',
												'woocommerce'
											),
											section.title
										) }
									>
										<Icon
											className={ section.key + '__icon' }
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
					</>
				) }
			/>
		);
	};

	const renderDashboardReports = () => {
		const { period, compare, before, after } = getDateParamsFromQuery(
			query,
			defaultDateRange
		);
		const { primary: primaryDate, secondary: secondaryDate } =
			getCurrentDates( query, defaultDateRange );
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
			<>
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
								onChangeHiddenBlocks={ onChangeHiddenBlocks(
									section.key
								) }
								onTitleUpdate={ onSectionTitleUpdate(
									section.key
								) }
								path={ path }
								defaultDateRange={ defaultDateRange }
								query={ query }
								title={ section.title }
								onMove={ partial( onMove, index ) }
								onRemove={ toggleVisibility( section.key ) }
								isFirst={
									section.key === visibleSectionKeys[ 0 ]
								}
								isLast={
									section.key ===
									visibleSectionKeys[
										visibleSectionKeys.length - 1
									]
								}
								filters={ filters }
							/>
						);
					}
					return null;
				} ) }
				{ renderAddMore() }
			</>
		);
	};

	return (
		<CurrencyContext.Provider
			value={ getFilteredCurrencyInstance( getQuery() ) }
		>
			{ renderDashboardReports() }
		</CurrencyContext.Provider>
	);
};

export default compose(
	withSelect( ( select ) => {
		const { woocommerce_default_date_range: defaultDateRange } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );

		return {
			defaultDateRange,
		};
	} )
)( CustomizableDashboard );
