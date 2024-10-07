/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';
import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useUserPreferences } from '../use-user-preferences';

describe( 'useUserPreferences() hook', () => {
	it( 'isRequesting is false before resolution has started', () => {
		registerStore( 'core', {
			reducer: () => ( {} ),
			selectors: {
				getEntity: jest.fn().mockReturnValue( undefined ),
				getCurrentUser: jest.fn().mockReturnValue( {} ),
				getLastEntitySaveError: jest.fn().mockReturnValue( {} ),
				hasStartedResolution: jest.fn().mockReturnValue( false ),
				hasFinishedResolution: jest.fn().mockReturnValue( false ),
			},
			actions: {
				receiveCurrentUser: jest.fn(),
				saveUser: jest.fn(),
			},
		} );

		const { result } = renderHook( () => useUserPreferences() );

		expect( result.current.isRequesting ).toBe( false );
	} );

	it( 'isRequesting is false after resolution has ended', () => {
		registerStore( 'core', {
			reducer: () => ( {} ),
			selectors: {
				getEntity: jest.fn().mockReturnValue( undefined ),
				getCurrentUser: jest.fn().mockReturnValue( {} ),
				getLastEntitySaveError: jest.fn().mockReturnValue( {} ),
				hasStartedResolution: jest.fn().mockReturnValue( true ),
				hasFinishedResolution: jest.fn().mockReturnValue( true ),
			},
			actions: {
				receiveCurrentUser: jest.fn(),
				saveUser: jest.fn(),
			},
		} );

		const { result } = renderHook( () => useUserPreferences() );

		expect( result.current.isRequesting ).toBe( false );
	} );

	it( 'isRequesting is true after resolution has started', () => {
		registerStore( 'core', {
			reducer: () => ( {} ),
			selectors: {
				getEntity: jest.fn().mockReturnValue( undefined ),
				getCurrentUser: jest.fn().mockReturnValue( {} ),
				getLastEntitySaveError: jest.fn().mockReturnValue( {} ),
				hasStartedResolution: jest.fn().mockReturnValue( true ),
				hasFinishedResolution: jest.fn().mockReturnValue( false ),
			},
			actions: {
				receiveCurrentUser: jest.fn(),
				saveUser: jest.fn(),
			},
		} );

		const { result } = renderHook( () => useUserPreferences() );

		expect( result.current.isRequesting ).toBe( true );
	} );

	it( 'Returns woocommerce_meta (JSON decoded) at root level', () => {
		registerStore( 'core', {
			reducer: () => ( {} ),
			selectors: {
				getEntity: jest.fn().mockReturnValue( undefined ),
				getCurrentUser: jest.fn().mockReturnValue( {
					woocommerce_meta: {
						dashboard_chart_type: '"line"',
						dashboard_sections:
							'[{"key":"leaderboards","title":"Leaderboards","isVisible":true,"icon":"editor-ol","hiddenBlocks":["coupons","customers"]}]',
						revenue_report_columns:
							'["coupons","taxes","shipping"]',
					},
				} ),
				getLastEntitySaveError: jest.fn().mockReturnValue( {} ),
				hasStartedResolution: jest.fn().mockReturnValue( true ),
				hasFinishedResolution: jest.fn().mockReturnValue( true ),
			},
			actions: {
				receiveCurrentUser: jest.fn(),
				saveUser: jest.fn(),
			},
		} );

		const { result } = renderHook( () => useUserPreferences() );

		expect( result.current.dashboard_chart_type ).toBe( 'line' );
		expect( result.current.dashboard_sections ).toMatchObject( [
			{
				hiddenBlocks: [ 'coupons', 'customers' ],
				icon: 'editor-ol',
				isVisible: true,
				key: 'leaderboards',
				title: 'Leaderboards',
			},
		] );
		expect( result.current.revenue_report_columns ).toEqual( [
			'coupons',
			'taxes',
			'shipping',
		] );
	} );

	it( 'Handles no valid meta keys', async () => {
		registerStore( 'core', {
			reducer: () => ( {} ),
			selectors: {
				getEntity: jest.fn().mockReturnValue( undefined ),
				getCurrentUser: jest.fn().mockReturnValue( {
					id: 1,
				} ),
				getLastEntitySaveError: jest.fn().mockReturnValue( {} ),
				hasStartedResolution: jest.fn().mockReturnValue( true ),
				hasFinishedResolution: jest.fn().mockReturnValue( true ),
			},
			actions: {
				receiveCurrentUser: jest.fn(),
				saveUser: jest.fn(),
			},
		} );

		const { result } = renderHook( () => useUserPreferences() );

		expect( typeof result.current.updateUserPreferences ).toBe(
			'function'
		);

		// Passing an empty object.
		const updateResult = await result.current.updateUserPreferences( {} );

		expect( updateResult ).toMatchObject( {
			error: new Error( 'Invalid woocommerce_meta data for update.' ),
			updatedUser: undefined,
		} );
	} );

	it( 'Saves user preferences', async () => {
		const saveUser = jest.fn().mockReturnValue( {
			// HACK alert!
			// This `type` property prevents the 'Actions may not have an undefined "type" property' error.
			// I tried to create this mock function as a generator, but it's not being called the
			// same way under test and didn't work.
			// Having to do this also prevents testing the saveUser() error condition.
			type: 'BOGUS_ACTION_HERE',
			id: 1,
			woocommerce_meta: {
				revenue_report_columns: '["shipping"]',
			},
		} );

		const receiveCurrentUser = jest.fn().mockReturnValue( {
			type: 'RECEIVE_CURRENT_USER',
			currentUser: {
				id: 1,
				woocommerce_meta: {
					revenue_report_columns: '["shipping"]',
				},
			},
		} );

		registerStore( 'core', {
			reducer: () => ( {} ),
			selectors: {
				getEntity: jest.fn().mockReturnValue( undefined ),
				getCurrentUser: jest.fn().mockReturnValue( {
					id: 1,
				} ),
				getLastEntitySaveError: jest.fn().mockReturnValue( {} ),
				hasStartedResolution: jest.fn().mockReturnValue( true ),
				hasFinishedResolution: jest.fn().mockReturnValue( true ),
			},
			actions: {
				receiveCurrentUser,
				saveUser,
			},
		} );

		const { result } = renderHook( () => useUserPreferences() );

		expect( typeof result.current.updateUserPreferences ).toBe(
			'function'
		);

		await act( async () => {
			const updateResult = await result.current.updateUserPreferences( {
				revenue_report_columns: [ 'shipping' ],
			} );

			expect( saveUser ).toHaveBeenCalledWith( {
				id: 1,
				woocommerce_meta: { revenue_report_columns: '["shipping"]' },
			} );
			expect( receiveCurrentUser ).toHaveBeenCalled();
			expect( updateResult ).toMatchObject( {
				updatedUser: {
					id: 1,
					woocommerce_meta: {
						revenue_report_columns: [ 'shipping' ],
					},
				},
			} );
		} );
	} );

	it( 'Polyfills saveUser() on older versions of WordPress', async () => {
		const receiveCurrentUser = jest.fn().mockReturnValue( {
			type: 'RECEIVE_CURRENT_USER',
			currentUser: {
				id: 1,
				woocommerce_meta: {
					revenue_report_columns: '["shipping"]',
				},
			},
		} );
		const addEntities = jest.fn().mockReturnValue( {
			type: 'BOGUG_ADD_ENTITIES',
		} );
		const saveEntityRecord = jest.fn().mockReturnValue( {
			type: 'BOGUG_SAVE_ENTITY_RECORD',
		} );
		registerStore( 'core', {
			reducer: () => ( {} ),
			selectors: {
				getCurrentUser: jest.fn().mockReturnValue( {
					id: 1,
				} ),
				getEntity: jest
					.fn()
					.mockReturnValueOnce( undefined )
					.mockReturnValueOnce( { name: 'user', kind: 'root' } ),
				getEntityRecord: jest.fn().mockReturnValue( {
					id: 1,
					woocommerce_meta: {
						revenue_report_columns: '["shipping"]',
					},
				} ),
				getLastEntitySaveError: jest.fn().mockReturnValue( {} ),
				hasStartedResolution: jest.fn().mockReturnValue( true ),
				hasFinishedResolution: jest.fn().mockReturnValue( true ),
			},
			actions: {
				addEntities,
				receiveCurrentUser,
				saveEntityRecord,
				// saveUser() left undefined to simulate WP 5.3.x.
			},
		} );

		const { result } = renderHook( () => useUserPreferences() );

		await act( async () => {
			const firstResult = await result.current.updateUserPreferences( {
				revenue_report_columns: [ 'shipping' ],
			} );

			// First calls should register the User entity.
			expect( addEntities ).toHaveBeenCalledWith( [
				{
					name: 'user',
					kind: 'root',
					baseURL: '/wp/v2/users',
					plural: 'users',
				},
			] );

			expect( saveEntityRecord ).toHaveBeenCalledWith( 'root', 'user', {
				id: 1,
				woocommerce_meta: { revenue_report_columns: '["shipping"]' },
			} );
			expect( receiveCurrentUser ).toHaveBeenCalled();
			expect( firstResult ).toMatchObject( {
				updatedUser: {
					id: 1,
					woocommerce_meta: {
						revenue_report_columns: [ 'shipping' ],
					},
				},
			} );

			await result.current.updateUserPreferences< {
				revenue_report_columns: string[];
			} >( {
				revenue_report_columns: [ 'shipping', 'taxes' ],
			} );

			// Subsequent calls should NOT register the User entity.
			expect( addEntities ).toHaveBeenCalledTimes( 1 );

			expect( saveEntityRecord ).toHaveBeenCalledWith( 'root', 'user', {
				id: 1,
				woocommerce_meta: {
					revenue_report_columns: '["shipping","taxes"]',
				},
			} );
		} );
	} );
} );
