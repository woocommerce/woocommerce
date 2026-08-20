/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useMemo, useRef } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { partial } from 'lodash';
import { Dropdown, Button } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import { Icon, plusCircleFilled } from '@wordpress/icons';
import { withSelect } from '@wordpress/data';
import { H } from '@woocommerce/components';
import { settingsStore, useUserPreferences } from '@woocommerce/data';
import { getQuery } from '@woocommerce/navigation';
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
import { ReportHeader } from '../analytics/components/report-header';

const DASHBOARD_FILTERS_FILTER = 'woocommerce_admin_dashboard_filters';

/**
 * @typedef {import('../analytics/report/index.js').filter} filter
 * @typedef {import('./default-sections.js').section} section
 */

/**
 * Add Report filters to the dashboard. None are added by default.
 *
 * @filter woocommerce_admin_dashboard_filters
 * @param {Array.<filter>} filters Report filters.
 */
const filters = applyFilters( DASHBOARD_FILTERS_FILTER, [] );

/**
 * A stored section is only usable when it carries the `key` that ties it back to
 * a default section. Corrupted `dashboard_sections` preferences have been seen
 * holding `null` entries, which used to crash the whole dashboard.
 *
 * @param {*} section Entry of the stored `dashboard_sections` preference.
 * @return {boolean} Whether the entry can be merged with a default section.
 */
const isValidSection = ( section ) =>
	!! section &&
	typeof section === 'object' &&
	typeof section.key === 'string';

/**
 * `hiddenBlocks` is spread over the default one and then dereferenced by every
 * section component, so a stored value that is not an array crashes the
 * dashboard just like a malformed entry does.
 *
 * @param {Object} section Well formed entry of the stored preference.
 * @return {boolean} Whether the entry's `hiddenBlocks` can be used as is.
 */
const hasUsableHiddenBlocks = ( section ) =>
	undefined === section.hiddenBlocks || Array.isArray( section.hiddenBlocks );

/**
 * Whether the stored `dashboard_sections` preference is well formed.
 *
 * @param {*} prefSections Stored `dashboard_sections` preference.
 * @return {boolean} Whether the preference can be used as is.
 */
const isValidSectionsPreference = ( prefSections ) =>
	Array.isArray( prefSections ) &&
	prefSections.length > 0 &&
	prefSections.every(
		( section ) =>
			isValidSection( section ) && hasUsableHiddenBlocks( section )
	);

/**
 * `icon` and `component` are React nodes, they must never be persisted.
 *
 * @param {section} section Section to persist.
 * @return {Object} Section without its React nodes.
 */
const toStorableSection = ( { icon, component, ...section } ) => section;

/**
 * Copy of the default sections, throwing a descriptive error when the
 * `woocommerce_dashboard_default_sections` filter returned something unusable.
 *
 * @return {Array.<section>} Default sections.
 */
const getDefaultSections = () => {
	if ( ! Array.isArray( defaultSections ) ) {
		throw new Error(
			`The \`defaultSections\` is not an array, please make sure \`${ DEFAULT_SECTIONS_FILTER }\` filter is used correctly.`
		);
	}

	return defaultSections.map( ( section ) => ( { ...section } ) );
};

export const mergeSectionsWithDefaults = ( prefSections ) => {
	const defaults = getDefaultSections();
	// Malformed entries are dropped instead of failing the whole dashboard.
	const validPrefSections = Array.isArray( prefSections )
		? prefSections.filter( isValidSection )
		: [];

	if ( validPrefSections.length === 0 ) {
		return defaults;
	}

	const defaultKeys = defaults.map( ( section ) => section.key );
	const prefKeys = validPrefSections.map( ( section ) => section.key );
	const keys = new Set( [ ...prefKeys, ...defaultKeys ] );
	const sections = [];

	keys.forEach( ( key ) => {
		const defaultSection = defaults.find(
			( section ) => section.key === key
		);
		if ( ! defaultSection ) {
			return;
		}
		const prefSection = validPrefSections.find(
			( section ) => section.key === key
		);

		const section = {
			...defaultSection,
			// A stored `icon` is a stale React node, the default one wins.
			...( prefSection ? toStorableSection( prefSection ) : {} ),
		};

		// The same goes for a `hiddenBlocks` that is not an array, which every
		// section component would blow up on.
		if ( ! Array.isArray( section.hiddenBlocks ) ) {
			section.hiddenBlocks = defaultSection.hiddenBlocks;
		}

		sections.push( section );
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
		updateUserPreferences( {
			dashboard_sections: newSections.map( toStorableSection ),
		} );
	};

	// Repair a corrupted `dashboard_sections` preference by storing the sections
	// the dashboard fell back to. Without this the merchant keeps loading the
	// broken value on every visit until they happen to customize a section.
	const hasRepairedSections = useRef( false );
	useEffect( () => {
		const prefSections = userPrefs.dashboard_sections;

		// An empty preference means the dashboard was never customized.
		if (
			hasRepairedSections.current ||
			! prefSections ||
			isValidSectionsPreference( prefSections )
		) {
			return;
		}

		hasRepairedSections.current = true;
		updateSections( sections );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ userPrefs.dashboard_sections ] );

	const updateSection = ( updatedKey, newSettings ) => {
		const newSections = sections.map( ( section ) => {
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
		const visibleSectionKeys = sections
			.filter( ( section ) => section.isVisible )
			.map( ( section ) => section.key );

		return (
			<>
				<ReportHeader
					report="dashboard"
					query={ query }
					path={ path }
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
			settingsStore
		).getSetting( 'wc_admin', 'wcAdminSettings' );

		return {
			defaultDateRange,
		};
	} )
)( CustomizableDashboard );
