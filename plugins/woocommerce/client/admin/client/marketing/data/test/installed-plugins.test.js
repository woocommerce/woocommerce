/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { getInstalledPlugins } from '../resolvers';
import { receiveInstalledPlugins } from '../actions';
import { API_NAMESPACE } from '../constants';
import reducer from '../reducer';

describe( 'marketing installed plugins', () => {
	it( 'starts with an empty list of installed plugins', () => {
		const state = reducer( undefined, { type: '@@INIT' } );

		expect( state.installedPlugins ).toEqual( [] );
	} );

	it( 'loads installed plugins from the REST API', () => {
		const plugins = [ { slug: 'mailpoet', status: 'activated' } ];
		const generator = getInstalledPlugins();

		expect( generator.next().value ).toEqual(
			apiFetch( {
				path: `${ API_NAMESPACE }/overview/installed-plugins`,
			} )
		);
		expect( generator.next( plugins ).value ).toEqual(
			receiveInstalledPlugins( plugins )
		);
		expect( generator.next().done ).toBe( true );
	} );
} );
