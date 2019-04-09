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

if ( 'development' === process.env.NODE_ENV ) {
	window.__FRESH_DATA_DEV_INFO__ = true;
}

function createWcApiStore() {
	const apiClient = createApiClient( 'wc-api', wcApiSpec );

	function getComponentSelectors( component ) {
		const componentRequirements = [];

		return {
			selectors: apiClient.getSelectors( componentRequirements ),
			onComplete: () => {
				if ( 0 === componentRequirements.length ) {
					apiClient.clearComponentRequirements( component );
				} else {
					apiClient.setComponentRequirements( component, componentRequirements );
				}
			},
		};
	}

	return {
		// The wrapped function for getSelectors is temporary code.
		//
		// @todo Remove the `() =>` after the `@wordpress/data` PR is merged:
		// https://github.com/WordPress/gutenberg/pull/11460
		//
		getSelectors: () => context => {
			const component = context && context.component ? context.component : context;
			return getComponentSelectors( component );
		},
		getActions() {
			const mutations = apiClient.getMutations();
			return mutations;
		},
		subscribe: apiClient.subscribe,
	};
}

registerGenericStore( 'wc-api', createWcApiStore() );
