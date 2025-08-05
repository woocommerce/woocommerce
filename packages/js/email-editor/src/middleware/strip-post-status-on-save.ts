/**
 * External dependencies
 */
import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { use } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { storeName as emailEditorStore } from '../store';

// Keep track of original actions per store
const originalActions = {};

// Which store and actions to wrap
const INTERCEPTED_ACTIONS = {
	core: [ 'saveEntityRecord' ],
};

export const initStripPostStatusOnSaveMiddleware = () => {
	// Already registered?
	if ( Object.keys( originalActions ).length > 0 ) {
		return;
	}

	use( ( registry ) => ( {
		dispatch: ( namespace ) => {
			const storeName =
				typeof namespace === 'object' ? namespace.name : namespace;

			// Only wrap the core store
			if ( ! INTERCEPTED_ACTIONS[ storeName ] ) {
				return registry.dispatch( namespace );
			}

			const actions = registry.dispatch( storeName );

			// Initialize namespace level objects if not yet done
			if ( ! originalActions[ storeName ] ) {
				originalActions[ storeName ] = {};
			}

			// Check if we need to intercept any actions for this store
			const actionsToIntercept = INTERCEPTED_ACTIONS[ storeName ].filter(
				( actionName ) => ! originalActions[ storeName ][ actionName ]
			);

			// Only proceed with the loop if there are actions to intercept
			if ( actionsToIntercept.length > 0 ) {
				// Only intercept actions we're interested in
				for ( const actionName of actionsToIntercept ) {
					originalActions[ storeName ][ actionName ] =
						actions[ actionName ];

					// Create a local rewritten action for saveEntityRecord
					actions[ actionName ] = async ( ...args ) => {
						const [ kind, name, recordOrId, options ] = args;

						// Validate kind and name
						if (
							typeof kind !== 'string' ||
							typeof name !== 'string'
						) {
							return await originalActions[ storeName ][
								actionName
							]( ...args );
						}

						const stripPostStatusOnSave = registry
							.select( emailEditorStore )
							.getStripPostStatusOnSave();
						const postType = registry
							.select( emailEditorStore )
							.getEmailPostType();

						// Proceed only for correct kind/name and when stripping is enabled
						if (
							! stripPostStatusOnSave ||
							kind !== 'postType' ||
							name !== postType
						) {
							return await originalActions[ storeName ][
								actionName
							]( ...args );
						}

						// Ensure recordOrId is object with numeric id
						if (
							typeof recordOrId !== 'object' ||
							recordOrId === null ||
							typeof recordOrId.id !== 'number'
						) {
							return await originalActions[ storeName ][
								actionName
							]( ...args );
						}

						// Get saved entity from store
						const post = registry
							.select( coreDataStore )
							.getEntityRecord(
								'postType',
								postType,
								recordOrId.id
							);

						// If post is missing or status is not defined, fallback to original action
						if ( ! post || typeof post.status !== 'string' ) {
							return await originalActions[ storeName ][
								actionName
							]( ...args );
						}

						// Update the status in editor store to match saved post
						registry
							.dispatch( editorStore )
							.editPost( { status: post.status } );

						// Remove status from payload sent to API
						const { status, ...sanitizedRecord } = recordOrId;
						return await originalActions[ storeName ][ actionName ](
							kind,
							name,
							sanitizedRecord,
							options
						);
					};
				}
			}

			return actions;
		},
	} ) );
};
