/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { isValidElement, useEffect, useMemo, useRef } from '@wordpress/element';
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
 * A section is only usable when it carries the `key` that ties it back to a
 * default section. Corrupted `dashboard_sections` preferences have been seen
 * holding `null` entries, which used to crash the whole dashboard. The key is
 * matched by strict equality and round trips through the stored JSON, so a
 * string and a finite number both tie a section back to its default.
 *
 * @param {*} section Entry of the stored `dashboard_sections` preference.
 * @return {boolean} Whether the entry can be merged with a default section.
 */
const isValidSection = ( section ) =>
	!! section &&
	typeof section === 'object' &&
	( typeof section.key === 'string' || Number.isFinite( section.key ) );

/**
 * Stored fields that take the dashboard down, or make a section unreachable,
 * when they hold the wrong type, mapped to the check a usable value passes.
 * `hiddenBlocks` is dereferenced by every section component, `title` is
 * rendered as a React child, and a non boolean `isVisible` hides a section
 * without listing it under "Add more sections". Each one is spread over the
 * default section, so a corrupted one wins unless it is caught here.
 * Validation, repair and the defaults all read this list so they cannot drift
 * apart.
 *
 * @type {Object.<string, function(*): boolean>}
 */
const FIELD_CHECKS = {
	hiddenBlocks: Array.isArray,
	isVisible: ( value ) => typeof value === 'boolean',
	title: ( value ) => typeof value === 'string',
};

/**
 * How to read a field a default section holds the wrong type for. Nothing sits
 * behind a default to patch it up from, so dropping the field would leave it
 * `undefined`: every section component dereferences `hiddenBlocks`, and an
 * `undefined` `isVisible` hides the section without listing it under "Add more
 * sections", which is the one state a merchant cannot get out of. The dashboard
 * has always rendered any truthy `isVisible` and printed any numeric `title`, so
 * the value is converted instead of dropped.
 *
 * @type {Object.<string, function(*): *>}
 */
const FIELD_FALLBACKS = {
	hiddenBlocks: () => [],
	isVisible: ( value ) => !! value,
	title: ( value ) => ( typeof value === 'number' ? String( value ) : '' ),
};

/**
 * Whether an entry of the stored preference carries usable values for the
 * fields the dashboard dereferences. A missing field is fine, the default
 * section provides it.
 *
 * @param {Object} section Well formed entry of the stored preference.
 * @return {boolean} Whether every field the dashboard reads can be used as is.
 */
const hasUsableFields = ( section ) =>
	Object.entries( FIELD_CHECKS ).every(
		( [ field, isUsable ] ) =>
			undefined === section[ field ] || isUsable( section[ field ] )
	);

/**
 * Whether an entry of the stored preference can be used without patching it up
 * from a default section.
 *
 * @param {*} section Entry of the stored `dashboard_sections` preference.
 * @return {boolean} Whether the entry can be used as is.
 */
const isUsableSection = ( section ) =>
	isValidSection( section ) && hasUsableFields( section );

/**
 * Whether the stored `dashboard_sections` preference is well formed.
 *
 * @param {*} prefSections Stored `dashboard_sections` preference.
 * @return {boolean} Whether the preference can be used as is.
 */
const isValidSectionsPreference = ( prefSections ) =>
	Array.isArray( prefSections ) &&
	prefSections.length > 0 &&
	prefSections.every( isUsableSection );

/**
 * Whether the dashboard was never customized. An unset preference and an empty
 * list both mean the defaults are in use, so there is nothing to repair.
 *
 * @param {*} prefSections Stored `dashboard_sections` preference.
 * @return {boolean} Whether the preference holds nothing.
 */
const isEmptySectionsPreference = ( prefSections ) =>
	! prefSections ||
	( Array.isArray( prefSections ) && prefSections.length === 0 );

/**
 * `icon` and `component` are React nodes, they must never be persisted.
 *
 * @param {section} section Section to persist.
 * @return {Object} Section without its React nodes.
 */
const toStorableSection = ( { icon, component, ...section } ) => section;

/**
 * Drops the fields the dashboard cannot read back, in place.
 *
 * @param {Object} section Section to strip.
 * @return {Object} The same section, holding only usable values.
 */
const deleteUnusableFields = ( section ) => {
	Object.entries( FIELD_CHECKS ).forEach( ( [ field, isUsable ] ) => {
		if ( ! isUsable( section[ field ] ) ) {
			delete section[ field ];
		}
	} );

	return section;
};

/**
 * A section to persist, without the fields the dashboard cannot use. There is
 * no default to patch a corrupted field up from at this point, so it is
 * dropped and whichever default section owns the key provides it on the next
 * read.
 *
 * @param {section} section Section to persist.
 * @return {Object} Section holding only values the dashboard can read back.
 */
const toUsableSection = ( section ) =>
	deleteUnusableFields( toStorableSection( section ) );

/**
 * A default section is the last fallback, so it has to stand on its own. An
 * entry with no key cannot be matched to a stored one and an entry with no
 * component cannot be rendered, so neither is a section the dashboard can
 * build.
 *
 * @param {*} section Entry returned by the default sections filter.
 * @return {boolean} Whether a section can be built from the entry.
 */
const isUsableDefaultSection = ( section ) =>
	isValidSection( section ) && !! section.component;

/**
 * A default section holding a value the dashboard can read for every field it
 * dereferences. Dropping the field is what the stored preference does, and it
 * only works there because a default backs it up. A default has nothing behind
 * it, so its fields are converted rather than dropped.
 *
 * @param {section} section Entry returned by the default sections filter.
 * @return {section} Copy of the section, holding only usable values.
 */
const toUsableDefaultSection = ( section ) => {
	const usable = { ...section };

	Object.entries( FIELD_CHECKS ).forEach( ( [ field, isUsable ] ) => {
		if ( ! isUsable( usable[ field ] ) ) {
			usable[ field ] = FIELD_FALLBACKS[ field ]( usable[ field ] );
		}
	} );

	return usable;
};

/**
 * Copy of the default sections, throwing a descriptive error when the
 * `woocommerce_dashboard_default_sections` filter returned something unusable.
 * The filter is a third party surface, so an entry no section can be built from
 * is dropped. Everything else is kept: the filter is a released extension point
 * that never enforced the documented types, and the dashboard is the last thing
 * standing between an extension's section and the merchant, so a field it
 * cannot read is converted instead of costing them the section.
 *
 * @return {Array.<section>} Default sections.
 */
const getDefaultSections = () => {
	if ( ! Array.isArray( defaultSections ) ) {
		throw new Error(
			`The \`defaultSections\` is not an array, please make sure \`${ DEFAULT_SECTIONS_FILTER }\` filter is used correctly.`
		);
	}

	return defaultSections
		.filter( isUsableDefaultSection )
		.map( toUsableDefaultSection );
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

		// The same goes for any field the dashboard cannot use, so a single
		// corrupted field costs the merchant that field and not their whole
		// section.
		Object.entries( FIELD_CHECKS ).forEach( ( [ field, isUsable ] ) => {
			if ( ! isUsable( section[ field ] ) ) {
				section[ field ] = defaultSection[ field ];
			}
		} );

		sections.push( section );
	} );

	return sections;
};

/**
 * The preference to store when repairing a corrupted one. Entries for keys the
 * dashboard does not know about are kept, so an extension that registers a
 * section does not lose its stored settings while it is deactivated. They are
 * appended after the known sections, so such an entry loses its stored
 * position.
 *
 * @param {Array.<section>} sections     Sections the dashboard fell back to.
 * @param {*}               prefSections Stored `dashboard_sections` preference.
 * @return {Array.<Object>} Sections to store.
 */
const toRepairedPreference = ( sections, prefSections ) => {
	const knownKeys = sections.map( ( section ) => section.key );
	const unknownSections = (
		Array.isArray( prefSections ) ? prefSections : []
	).filter(
		( section ) =>
			isValidSection( section ) && ! knownKeys.includes( section.key )
	);

	return [ ...sections, ...unknownSections ].map( toUsableSection );
};

const CustomizableDashboard = ( { defaultDateRange, path, query } ) => {
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();

	const sections = useMemo(
		() => mergeSectionsWithDefaults( userPrefs.dashboard_sections ),
		[ userPrefs.dashboard_sections ]
	);

	// The update callbacks are handed to the section components, so a third
	// party one can supply a value the dashboard cannot read back. Sanitizing
	// here keeps a preference the dashboard wrote from needing a repair.
	const updateSections = ( newSections ) => {
		updateUserPreferences( {
			dashboard_sections: newSections.map( toUsableSection ),
		} );
	};

	// Repair a corrupted `dashboard_sections` preference by storing the sections
	// the dashboard fell back to. Without this the merchant keeps loading the
	// broken value on every visit until they happen to customize a section.
	const hasAttemptedRepair = useRef( false );
	useEffect( () => {
		const prefSections = userPrefs.dashboard_sections;

		if (
			hasAttemptedRepair.current ||
			isEmptySectionsPreference( prefSections ) ||
			isValidSectionsPreference( prefSections )
		) {
			return;
		}

		hasAttemptedRepair.current = true;

		const repaired = toRepairedPreference( sections, prefSections );

		// Nothing to fall back to, for example when the default sections filter
		// emptied the list. Storing this would only be repaired again on the
		// next visit, one write per page load and no gain.
		if ( ! isValidSectionsPreference( repaired ) ) {
			return;
		}

		updateUserPreferences( { dashboard_sections: repaired } );
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
										{ isValidElement( section.icon ) && (
											<Icon
												className={
													section.key + '__icon'
												}
												icon={ section.icon }
												size={ 30 }
											/>
										) }
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
