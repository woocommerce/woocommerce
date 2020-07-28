/**
 * External dependencies
 */
import { omit, mapValues } from 'lodash';
import memize from 'memize';
import { select, subscribe } from '@wordpress/data';

export function __experimentalResolveSelect( reducerKey ) {
	return getResolveSelectors( select( reducerKey ) );
}

/**
 * Returns a promise that resolves once a selector has finished resolving.
 * This is directly pulled from https://github.com/WordPress/gutenberg/blob/909c9274b2440de5f6049ffddfcc8e0e6158df2d/packages/data/src/registry.js#L91-L130
 * and will be removed in favor of the @wordpress/data function.
 */
const getResolveSelectors = memize(
	( selectors ) => {
		return mapValues(
			omit( selectors, [
				'getIsResolving',
				'hasStartedResolution',
				'hasFinishedResolution',
				'isResolving',
				'getCachedResolvers',
			] ),
			( selector, selectorName ) => {
				return ( ...args ) => {
					return new Promise( ( resolve ) => {
						const hasFinished = () =>
							selectors.hasFinishedResolution(
								selectorName,
								args
							);
						const getResult = () => selector.apply( null, args );

						// trigger the selector (to trigger the resolver)
						const result = getResult();
						if ( hasFinished() ) {
							return resolve( result );
						}

						const unsubscribe = subscribe( () => {
							if ( hasFinished() ) {
								unsubscribe();
								resolve( getResult() );
							}
						} );
					} );
				};
			}
		);
	},
	{ maxSize: 1 }
);
