// Partially copied from: https://github.com/Automattic/wp-calypso/blob/6241a94e52e60a662245bdb06ecae9c724bcec5f/apps/wpcom-block-editor/src/wpcom/features/tracking.js

/**
 * External dependencies
 */
import { use } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { trackEvent } from '~/customize-store/tracking';

const rewrittenActions = {};
const originalActions = {};

/**
 * Tracker can be
 * - string - which means it is an event name and should be tracked as such automatically
 * - function - in case you need to load additional properties from the action.
 *
 * @type {Object}
 */
const REDUX_TRACKING = {
	'core/block-editor': {
		moveBlocksUp: () =>
			trackEvent(
				'customize_your_store_assembler_pattern_move_up_click'
			),
		moveBlocksDown: () =>
			trackEvent(
				'customize_your_store_assembler_pattern_move_down_click'
			),
	},
};

use(
	( registry ) => ( {
		dispatch: ( namespace ) => {
			const namespaceName =
				typeof namespace === 'object' ? namespace.name : namespace;
			const actions = { ...registry.dispatch( namespaceName ) };

			const trackers = REDUX_TRACKING[ namespaceName ];

			// Initialize namespace level objects if not yet done.
			if ( ! rewrittenActions[ namespaceName ] ) {
				rewrittenActions[ namespaceName ] = {};
			}
			if ( ! originalActions[ namespaceName ] ) {
				originalActions[ namespaceName ] = {};
			}

			if ( trackers ) {
				Object.keys( trackers ).forEach( ( actionName ) => {
					const originalAction = actions[ actionName ];
					const tracker = trackers[ actionName ];
					// If we havent stored the originalAction, or it is no longer the same as the
					// one we last wrote a corresponding rewrittenAction for, we need to update.
					if (
						! originalActions[ namespaceName ][ actionName ] ||
						originalActions[ namespaceName ][ actionName ] !==
							originalAction
					) {
						// Save the originalAction and rewrittenAction for future reference.
						originalActions[ namespaceName ][ actionName ] =
							originalAction;
						rewrittenActions[ namespaceName ][ actionName ] = (
							...args
						) => {
							// We use a try-catch here to make sure the `originalAction`
							// is always called. We don't want to break the original
							// behaviour when our tracking throws an error.
							try {
								if ( typeof tracker === 'string' ) {
									// Simple track - just based on the event name.
									trackEvent( tracker );
								} else if ( typeof tracker === 'function' ) {
									// Advanced tracking - call function.
									tracker( ...args );
								}
							} catch ( err ) {
								// eslint-disable-next-line no-console
								console.error( err );
							}
							return originalAction( ...args );
						};
					}
					// Replace the action in the registry with the rewrittenAction.
					actions[ actionName ] =
						rewrittenActions[ namespaceName ][ actionName ];
				} );
			}
			return actions;
		},
	} ),
	{}
);
