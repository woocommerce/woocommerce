/**
 * External dependencies
 */
import { use } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { recordEvent } from '.';
import { editorCurrentPostType, editorCurrentPostId } from '../store';

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

const TRACKED_STORE_EVENTS = {
	'core/editor': {
		setDeviceType: trackSetDeviceType,
	},
	core: {
		deleteEntityRecord: trackDeleteEntityRecord,
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
			const namespaceName =
				typeof namespace === 'object' ? namespace.name : namespace;
			const actions = registry.dispatch( namespaceName );
			const trackers = TRACKED_STORE_EVENTS[ namespaceName ];

			if ( ! trackers ) {
				return actions;
			}

			// Initialize namespace level objects if not yet done.
			if ( ! rewrittenActions[ namespaceName ] ) {
				rewrittenActions[ namespaceName ] = {};
			}
			if ( ! originalActions[ namespaceName ] ) {
				originalActions[ namespaceName ] = {};
			}

			for ( const [ action, event ] of Object.entries( trackers ) ) {
				if ( ! originalActions[ namespaceName ][ action ] ) {
					originalActions[ namespaceName ][ action ] =
						actions[ action ];
					rewrittenActions[ namespaceName ][ action ] = (
						...args
					) => {
						try {
							if ( typeof event === 'function' ) {
								event( ...args );
							} else if ( typeof event === 'string' ) {
								recordEvent( event );
							}
						} catch ( error ) {
							console.error( 'Error tracking event', error );
						}
						originalActions[ namespaceName ][ action ]( ...args );
					};
				}
				actions[ action ] = rewrittenActions[ namespaceName ][ action ];
			}

			return actions;
		},
	} ) );
};
