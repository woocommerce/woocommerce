/**
 * External dependencies
 */
import { use, subscribe, select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { storeName as emailEditorStore } from '../store';

// Store original actions and rewritten actions
const originalActions = {};
const rewrittenActions = {};

// Define which stores and actions we want to intercept
const INTERCEPTED_ACTIONS = {
	core: [ 'saveEditedEntityRecord', 'saveEntityRecord' ],
};

export const initContentValidationMiddleware = () => {
	// Check if middleware is already registered to avoid duplicate registrations
	if ( Object.keys( originalActions ).length > 0 ) {
		return;
	}

	use( ( registry ) => ( {
		dispatch: ( namespace ) => {
			const storeName =
				typeof namespace === 'object' ? namespace.name : namespace;

			// Only intercept actions for stores we're interested in
			if ( ! INTERCEPTED_ACTIONS[ storeName ] ) {
				return registry.dispatch( storeName );
			}

			const actions = registry.dispatch( storeName );

			// Initialize namespace level objects if not yet done
			if ( ! rewrittenActions[ storeName ] ) {
				rewrittenActions[ storeName ] = {};
			}
			if ( ! originalActions[ storeName ] ) {
				originalActions[ storeName ] = {};
			}

			// Only intercept actions we're interested in
			for ( const actionName of INTERCEPTED_ACTIONS[ storeName ] ) {
				if ( ! originalActions[ storeName ][ actionName ] ) {
					originalActions[ storeName ][ actionName ] =
						actions[ actionName ];

					if (
						actionName === 'saveEditedEntityRecord' ||
						actionName === 'saveEntityRecord'
					) {
						rewrittenActions[ storeName ][ actionName ] = async (
							...args
						) => {
							// Get validation function from the store
							const validation = registry
								.select( emailEditorStore )
								.getContentValidation();

							const validateContent = validation?.validateContent;

							if ( validateContent ) {
								let isValid = false;
								try {
									// Validate content before saving
									isValid = validateContent();
								} catch ( error ) {
									// If there's an error, we'll consider the validation failed
									isValid = false;
								}

								if ( ! isValid ) {
									// Return a rejected promise instead of throwing an error
									return Promise.reject(
										new Error(
											'Content validation failed.'
										)
									);
								}
							}

							try {
								// If validation passes, call the original function
								return await originalActions[ storeName ][
									actionName
								]( ...args );
							} catch ( error ) {
								// For other types of errors, just rethrow them
								throw error;
							}
						};

						actions[ actionName ] =
							rewrittenActions[ storeName ][ actionName ];
					}
				}
			}

			return actions;
		},
	} ) );

	// Remove error notice that could be confusing for users.
	subscribe( () => {
		select( 'core/notices' )
			.getNotices()
			.forEach( ( notice ) => {
				if ( /^Saving failed/.test( notice.content ) ) {
					dispatch( 'core/notices' ).removeNotice( notice.id );
				}
			} );
	} );
};
