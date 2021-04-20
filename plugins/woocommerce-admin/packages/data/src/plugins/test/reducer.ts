/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import { ACTION_TYPES as TYPES } from '../action-types';
import { PluginsState } from '../types';
import { Actions } from '../actions';

const defaultState: PluginsState = {
	active: [],
	installed: [],
	requesting: {},
	errors: {},
	jetpackConnectUrls: {},
	recommended: {},
};

describe( 'plugins reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle UPDATE_ACTIVE_PLUGINS with replace', () => {
		const state = reducer(
			{
				...defaultState,
				active: [ 'plugins', 'to', 'overwrite' ],
			},
			{
				type: TYPES.UPDATE_ACTIVE_PLUGINS,
				active: [ 'jetpack' ],
				replace: true,
			} as Actions
		);

		/* eslint-disable dot-notation */

		expect( state.requesting[ 'getActivePlugins' ] ).toBe( false );
		expect( state.errors[ 'getActivePlugins' ] ).toBe( false );
		/* eslint-enable dot-notation */

		expect( state.active ).toHaveLength( 1 );
		expect( state.active[ 0 ] ).toBe( 'jetpack' );
	} );

	it( 'should handle UPDATE_ACTIVE_PLUGINS with active plugins', () => {
		const state = reducer(
			{
				...defaultState,
				active: [ 'jetpack' ],
				installed: [ 'jetpack' ],
				requesting: {},
				errors: {},
			},
			{
				type: TYPES.UPDATE_ACTIVE_PLUGINS,
				active: [ 'woocommerce-services' ],
			} as Actions
		);

		/* eslint-disable dot-notation */

		expect( state.requesting[ 'getActivePlugins' ] ).toBe( false );
		expect( state.errors[ 'getActivePlugins' ] ).toBe( false );
		/* eslint-enable dot-notation */

		expect( state.active ).toHaveLength( 2 );
		expect( state.active[ 1 ] ).toBe( 'woocommerce-services' );
	} );

	it( 'should handle UPDATE_INSTALLED_PLUGINS with replace', () => {
		const state = reducer(
			{
				...defaultState,
				active: [ 'plugins', 'to', 'overwrite' ],
			},
			{
				type: TYPES.UPDATE_INSTALLED_PLUGINS,
				installed: [ 'jetpack' ],
				replace: true,
			} as Actions
		);

		/* eslint-disable dot-notation */

		expect( state.requesting[ 'getInstalledPlugins' ] ).toBe( false );
		expect( state.errors[ 'getInstalledPlugins' ] ).toBe( false );
		/* eslint-enable dot-notation */

		expect( state.installed ).toHaveLength( 1 );
		expect( state.installed[ 0 ] ).toBe( 'jetpack' );
	} );

	it( 'should handle UPDATE_INSTALLED_PLUGINS with installed plugins', () => {
		const state = reducer(
			{
				...defaultState,
				active: [ 'jetpack' ],
				installed: [ 'jetpack' ],
				requesting: {},
				errors: {},
			},
			{
				type: TYPES.UPDATE_INSTALLED_PLUGINS,
				installed: [ 'woocommerce-services' ],
			} as Actions
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
		} as Actions );

		/* eslint-disable dot-notation */

		expect( state.requesting[ 'getInstalledPlugins' ] ).toBeTruthy();
		/* eslint-enable dot-notation */
	} );

	it( 'should handle SET_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			selector: 'getInstalledPlugins',
			error: { jetpack: [ 'error' ] },
		} as Actions );

		/* eslint-disable dot-notation */

		expect(
			( state.errors[ 'getInstalledPlugins' ] as Record<
				string,
				string[]
			> ).jetpack[ 0 ]
		).toBe( 'error' );
		expect( state.requesting[ 'getInstalledPlugins' ] ).toBe( false );
		/* eslint-enable dot-notation */
	} );

	it( 'should handle UPDATE_JETPACK_CONNECTION', () => {
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_JETPACK_CONNECTION,
			jetpackConnection: true,
		} as Actions );

		expect( state.jetpackConnection ).toBe( true );
	} );

	it( 'should handle UPDATE_JETPACK_CONNECT_URL', () => {
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_JETPACK_CONNECT_URL,
			jetpackConnectUrl: 'http://connect.com',
			redirectUrl: 'http://redirect.com',
		} as Actions );

		expect( state.jetpackConnectUrls[ 'http://redirect.com' ] ).toBe(
			'http://connect.com'
		);
	} );
} );
