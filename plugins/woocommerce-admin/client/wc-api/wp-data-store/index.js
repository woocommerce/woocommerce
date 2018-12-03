/** @format */
/**
 * External dependencies
 */
import { registerGenericStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import createApiClient from './create-api-client';
import wcApiSpec from '../wc-api-spec';

function createWcApiStore() {
	const apiClient = createApiClient( 'wc-api', wcApiSpec );

	function getComponentSelectors( component ) {
		const componentRequirements = [];
		const apiSelectors = apiClient.getSelectors( componentRequirements );

		apiClient.clearComponentRequirements( component );

		return Object.keys( apiSelectors ).reduce( ( componentSelectors, selectorName ) => {
			componentSelectors[ selectorName ] = ( ...args ) => {
				const result = apiSelectors[ selectorName ]( ...args );
				apiClient.setComponentRequirements( component, componentRequirements );
				return result;
			};
			return componentSelectors;
		}, {} );
	}

	return {
		// The wrapped function for getSelectors is temporary code.
		//
		// TODO: Remove the `() =>` after the `@wordpress/data` PR is merged:
		// https://github.com/WordPress/gutenberg/pull/11460
		//
		getSelectors: () => context => {
			const component = context && context.component ? context.component : context;
			return getComponentSelectors( component );
		},
		getActions() {
			// TODO: Add mutations here.
			return {};
		},
		subscribe: apiClient.subscribe,
	};
}

registerGenericStore( 'wc-api', createWcApiStore() );
