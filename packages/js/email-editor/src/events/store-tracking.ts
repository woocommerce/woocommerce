/**
 * External dependencies
 */
import { use, select } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { store as preferencesStore } from '@wordpress/preferences';
import { store as editorStore } from '@wordpress/editor';
/**
 * Internal dependencies
 */
import { recordEvent } from '.';
import { editorCurrentPostType, editorCurrentPostId } from '../store';

/**
 * Handler functions for tracking individual events recorder by the listening to store actions.
 */
const trackSetDeviceType = ( deviceType: string ) => {
	recordEvent(
		`header_preview_dropdown_${ deviceType.toLowerCase() }_selected`
	);
};

const trackDeleteEntityRecord = ( _entity, type, id ) => {
	if ( type === editorCurrentPostType && id === editorCurrentPostId ) {
		recordEvent( 'trash_modal_move_to_trash_button_clicked' );
	}
};

const trackSetIsInserterOpened = ( isOpened: boolean ) => {
	// @ts-expect-error - isInserterOpened is not in editor types
	const isInserterOpened = select( editorStore ).isInserterOpened();
	if ( isInserterOpened === isOpened ) {
		return;
	}
	recordEvent(
		`header_inserter_sidebar_${ isOpened ? 'opened' : 'closed' }`
	);
};

const trackSetIsListViewOpened = ( isOpened: boolean ) => {
	// @ts-expect-error - isListViewOpened is not in editor types
	const isListViewOpened = select( editorStore ).isListViewOpened();
	if ( isListViewOpened === isOpened ) {
		return;
	}
	recordEvent(
		`header_listview_sidebar_${ isOpened ? 'opened' : 'closed' }`
	);
};

const trackSetPreference = ( scope, name, value ) => {
	const valueBeforeToggle = select( preferencesStore ).get(
		scope,
		name
	);
	if ( valueBeforeToggle === value ) {
		return;
	}
	const trackedPreferences = {
		focusMode: 'focus_mode_toggle',
		fullscreenMode: 'full_screen_mode_toggle',
		distractionFree: 'distraction_free_toggle',
		fixedToolbar: 'fixed_toolbar_toggle',
	};
	if ( trackedPreferences[ name ] ) {
		recordEvent( trackedPreferences[ name ], { isEnabled: value } );
	}
};

/**
 * List of store actions to be tracked.
 */
const TRACKED_STORE_EVENTS = {
	'core/editor': {
		autosave: 'editor_content_auto_saved',
		setDeviceType: trackSetDeviceType,
		setIsInserterOpened: trackSetIsInserterOpened,
		setIsListViewOpened: trackSetIsListViewOpened,
	},
	core: {
		deleteEntityRecord: trackDeleteEntityRecord,
	},
	'core/preferences': {
		set: trackSetPreference,
	},
};

const rewrittenActions = {};
const originalActions = {};

export const initStoreTracking = () => {
	const isEventTrackingEnabled = applyFilters(
		'woocommerce_email_editor_events_tracking_enabled',
		false
	);

	if ( ! isEventTrackingEnabled ) {
		return;
	}

	use( ( registry ) => ( {
		dispatch: ( namespace ) => {
			const storeName =
				typeof namespace === 'object' ? namespace.name : namespace;
			const actions = registry.dispatch( storeName );
			const trackers = TRACKED_STORE_EVENTS[ storeName ];

			if ( ! trackers ) {
				return actions;
			}

			// Initialize namespace level objects if not yet done.
			if ( ! rewrittenActions[ storeName ] ) {
				rewrittenActions[ storeName ] = {};
			}
			if ( ! originalActions[ storeName ] ) {
				originalActions[ storeName ] = {};
			}

			for ( const [ action, event ] of Object.entries( trackers ) ) {
				if ( ! originalActions[ storeName ][ action ] ) {
					originalActions[ storeName ][ action ] = actions[ action ];
					rewrittenActions[ storeName ][ action ] = ( ...args ) => {
						try {
							if ( typeof event === 'function' ) {
								event( ...args );
							} else if ( typeof event === 'string' ) {
								recordEvent( event );
							}
						} catch ( error ) {
							console.error( 'Error tracking event', error );
						}
						originalActions[ storeName ][ action ]( ...args );
					};
				}
				actions[ action ] = rewrittenActions[ storeName ][ action ];
			}

			return actions;
		},
	} ) );
};
