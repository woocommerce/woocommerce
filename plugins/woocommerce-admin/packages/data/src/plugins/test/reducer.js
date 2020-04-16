/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = {
	active: [],
	installed: [],
	requesting: {},
	errors: {},
	jetpackConnectUrls: {},
};

describe( 'plugins reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle UPDATE_ACTIVE_PLUGINS', () => {
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_ACTIVE_PLUGINS,
			active: [ 'jetpack' ],
		} );

		/* eslint-disable dot-notation */

		expect( state.requesting[ 'getActivePlugins' ] ).toBe( false );
		expect( state.errors[ 'getActivePlugins' ] ).toBe( false );
		/* eslint-enable dot-notation */

		expect( state.active ).toHaveLength( 1 );
		expect( state.active[ 0 ] ).toBe( 'jetpack' );
	} );

	it( 'should handle UPDATE_INSTALLED_PLUGINS', () => {
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_INSTALLED_PLUGINS,
			installed: [ 'jetpack' ],
		} );

		/* eslint-disable dot-notation */

		expect( state.requesting[ 'getInstalledPlugins' ] ).toBe( false );
		expect( state.errors[ 'getInstalledPlugins' ] ).toBe( false );
		/* eslint-enable dot-notation */

		expect( state.installed ).toHaveLength( 1 );
		expect( state.installed[ 0 ] ).toBe( 'jetpack' );
	} );

	it( 'should handle UPDATE_INSTALLED_PLUGINS with added plugins', () => {
		const state = reducer(
			{
				active: [ 'jetpack' ],
				installed: [ 'jetpack' ],
				requesting: {},
				errors: {},
			},
			{
				type: TYPES.UPDATE_INSTALLED_PLUGINS,
				installed: null,
				added: [ 'woocommerce-services' ],
			}
		);

		/* eslint-disable dot-notation */

		expect( state.requesting[ 'getInstalledPlugins' ] ).toBe( false );
		expect( state.errors[ 'getInstalledPlugins' ] ).toBe( false );
		/* eslint-enable dot-notation */

		expect( state.installed ).toHaveLength( 2 );
		expect( state.installed[ 1 ] ).toBe( 'woocommerce-services' );
	} );

	it( 'should handle SET_IS_REQUESTING', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_IS_REQUESTING,
			selector: 'getInstalledPlugins',
			isRequesting: true,
		} );

		/* eslint-disable dot-notation */

		expect( state.requesting[ 'getInstalledPlugins' ] ).toBeTruthy();
		/* eslint-enable dot-notation */
	} );

	it( 'should handle SET_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			selector: 'getInstalledPlugins',
			error: { code: 'error' },
		} );

		/* eslint-disable dot-notation */

		expect( state.errors[ 'getInstalledPlugins' ].code ).toBe( 'error' );
		expect( state.requesting[ 'getInstalledPlugins' ] ).toBe( false );
		/* eslint-enable dot-notation */
	} );

	it( 'should handle UPDATE_JETPACK_CONNECTION', () => {
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_JETPACK_CONNECTION,
			jetpackConnection: true,
		} );

		expect( state.jetpackConnection ).toBe( true );
	} );

	it( 'should handle UPDATE_JETPACK_CONNECT_URL', () => {
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_JETPACK_CONNECT_URL,
			jetpackConnectUrl: 'http://connect.com',
			redirectUrl: 'http://redirect.com',
		} );

		expect( state.jetpackConnectUrls[ 'http://redirect.com' ] ).toBe(
			'http://connect.com'
		);
	} );
} );
