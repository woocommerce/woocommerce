/**
 * External dependencies
 */
import { interpret } from 'xstate';
import { waitFor } from 'xstate/lib/waitFor';

/**
 * Internal dependencies
 */
import { designWithNoAiStateMachineDefinition } from '../state-machine';

const createMockMachine = ( {
	services,
	guards,
	actions,
}: Parameters<
	typeof designWithNoAiStateMachineDefinition.withConfig
>[ 0 ] ) => {
	const machineWithConfig = designWithNoAiStateMachineDefinition.withConfig( {
		services,
		guards,
		actions,
	} );

	return machineWithConfig;
};

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock(
	'@wordpress/edit-site/build-module/components/global-styles/global-styles-provider',
	() => jest.fn()
);

describe( 'Design Without AI state machine', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'navigate state', () => {
		it( 'should start with the navigate state', async () => {
			const expectedValue = 'navigate';

			const actualState =
				designWithNoAiStateMachineDefinition.initialState;

			expect( actualState.matches( expectedValue ) ).toBeTruthy();
		} );

		it( 'should check the url', () => {
			const hasStepInUrl = jest.fn( () => true );
			const machine = designWithNoAiStateMachineDefinition.withConfig( {
				guards: {
					hasStepInUrl,
				},
			} );

			const machineInterpret = interpret( machine ).start();

			expect( hasStepInUrl ).toBeCalled();
			machineInterpret.stop();
		} );

		it( 'should transit to preAssembleSite state when the url is /design', () => {
			const hasStepInUrl = jest.fn( () => true );
			const machine = designWithNoAiStateMachineDefinition.withConfig( {
				guards: {
					hasStepInUrl,
				},
			} );

			const machineInterpret = interpret( machine ).start();

			expect(
				machineInterpret.getSnapshot().matches( 'preAssembleSite' )
			).toBeTruthy();

			machineInterpret.stop();
		} );

		it( "should not transit to preAssembleSite state when the url isn't /design", () => {
			const hasStepInUrl = jest.fn( () => false );
			const machine = designWithNoAiStateMachineDefinition.withConfig( {
				guards: {
					hasStepInUrl,
				},
			} );

			const machineInterpret = interpret( machine ).start();

			expect(
				machineInterpret.getSnapshot().matches( 'preAssembleSite' )
			).toBeFalsy();
		} );
	} );

	describe( 'preAssembleSite state', () => {
		it( 'should invoke `redirectToIntroWithError` when `installAndActivateTheme` service fails', async () => {
			const initialState =
				'preAssembleSite.preApiCallLoader.installAndActivateTheme';

			const installAndActivateThemeMock = jest.fn( () =>
				Promise.reject()
			);
			const assembleSiteMock = jest.fn( () => Promise.resolve() );
			const createProductsMock = jest.fn( () =>
				Promise.resolve( {
					success: true,
				} )
			);
			const redirectToIntroWithErrorMock = jest.fn();

			const machine = createMockMachine( {
				services: {
					installAndActivateTheme: installAndActivateThemeMock,
					assembleSite: assembleSiteMock,
					createProducts: createProductsMock,
				},
				actions: {
					redirectToIntroWithError: redirectToIntroWithErrorMock,
				},
			} );

			const state = machine.getInitialState( initialState );

			const actor = interpret( machine ).start( state );

			await waitFor( actor, ( currentState ) => {
				return currentState.matches(
					'preAssembleSite.preApiCallLoader.installAndActivateTheme.pending'
				);
			} );

			expect( installAndActivateThemeMock ).toHaveBeenCalled();
			expect( redirectToIntroWithErrorMock ).toHaveBeenCalled();
		} );
		it( 'should invoke `installAndActivateTheme` service', async () => {
			const initialState =
				'preAssembleSite.preApiCallLoader.installAndActivateTheme';
			const installAndActivateThemeMock = jest.fn( () =>
				Promise.resolve()
			);
			const assembleSiteMock = jest.fn( () => Promise.resolve() );
			const createProductsMock = jest.fn( () =>
				Promise.resolve( {
					success: true,
				} )
			);

			const machine = createMockMachine( {
				services: {
					installAndActivateTheme: installAndActivateThemeMock,
					assembleSite: assembleSiteMock,
					createProducts: createProductsMock,
				},
				actions: {
					redirectToAssemblerHub: jest.fn(),
				},
			} );

			const state = machine.getInitialState( initialState );

			const actor = interpret( machine ).start( state );

			await waitFor( actor, ( currentState ) => {
				return currentState.matches(
					'preAssembleSite.preApiCallLoader.installAndActivateTheme.setupStore'
				);
			} );

			await actor.stop();

			expect( installAndActivateThemeMock ).toHaveBeenCalled();
		} );

		it( 'should invoke `redirectToIntroWithError` when `assembleSite` service fails', async () => {
			const initialState =
				'preAssembleSite.preApiCallLoader.installAndActivateTheme';

			const assembleSiteMock = jest.fn( () => Promise.reject() );
			const createProductsMock = jest.fn( () => {
				return Promise.resolve( {
					success: true,
				} );
			} );
			const installAndActivateThemeMock = jest.fn( () =>
				Promise.resolve()
			);

			const redirectToIntroWithErrorMock = jest.fn();

			const machine = createMockMachine( {
				services: {
					assembleSite: assembleSiteMock,
					createProducts: createProductsMock,
					installAndActivateTheme: installAndActivateThemeMock,
				},
				actions: {
					redirectToIntroWithError: redirectToIntroWithErrorMock,
				},
			} );

			const state = machine.getInitialState( initialState );

			const actor = interpret( machine ).start( state );

			await waitFor( actor, ( currentState ) => {
				return currentState.matches(
					'preAssembleSite.preApiCallLoader.installAndActivateTheme.setupStore'
				);
			} );

			expect( assembleSiteMock ).toHaveBeenCalled();
			expect( redirectToIntroWithErrorMock ).toHaveBeenCalled();
		} );

		it( 'should invoke `assembleSite` service', async () => {
			const initialState =
				'preAssembleSite.preApiCallLoader.installAndActivateTheme';

			const assembleSiteMock = jest.fn( () => Promise.resolve() );
			const installAndActivateThemeMock = jest.fn( () =>
				Promise.resolve()
			);
			const createProductsMock = jest.fn( () =>
				Promise.resolve( {
					success: true,
				} )
			);

			const machine = createMockMachine( {
				services: {
					assembleSite: assembleSiteMock,
					createProducts: createProductsMock,
					installAndActivateTheme: installAndActivateThemeMock,
				},
				actions: {
					redirectToAssemblerHub: jest.fn(),
				},
			} );

			const state = machine.getInitialState( initialState );

			const actor = interpret( machine ).start( state );

			await waitFor( actor, ( currentState ) => {
				return currentState.matches(
					'preAssembleSite.preApiCallLoader.installAndActivateTheme.setupStore.assembleSite.success'
				);
			} );

			expect( assembleSiteMock ).toHaveBeenCalled();

			const finalState = await waitFor( actor, ( currentState ) => {
				return currentState.matches( 'showAssembleHub' );
			} );

			expect( finalState.matches( 'showAssembleHub' ) ).toBeTruthy();
		} );

		it( 'should invoke `redirectToIntroWithError` when `createProducts` service fails', async () => {
			const initialState =
				'preAssembleSite.preApiCallLoader.installAndActivateTheme.setupStore.createProducts';

			const createProductsMock = jest.fn( () => Promise.reject() );
			const assembleSiteMock = jest.fn( () => Promise.resolve() );
			const installAndActivateThemeMock = jest.fn( () =>
				Promise.resolve()
			);

			const redirectToIntroWithErrorMock = jest.fn();

			const machine = createMockMachine( {
				services: {
					createProducts: createProductsMock,
					assembleSite: assembleSiteMock,
					installAndActivateTheme: installAndActivateThemeMock,
				},
				actions: {
					redirectToIntroWithError: redirectToIntroWithErrorMock,
				},
			} );

			const state = machine.getInitialState( initialState );

			const actor = interpret( machine ).start( state );

			await waitFor( actor, ( currentState ) => {
				return currentState.matches(
					'preAssembleSite.preApiCallLoader.installAndActivateTheme.setupStore.createProducts.pending'
				);
			} );

			expect( createProductsMock ).toHaveBeenCalled();
			expect( redirectToIntroWithErrorMock ).toHaveBeenCalled();
		} );

		it( 'should invoke `createProducts` service', async () => {
			const initialState =
				'preAssembleSite.preApiCallLoader.installAndActivateTheme.setupStore.createProducts';

			const createProductsMock = jest.fn( () =>
				Promise.resolve( {
					success: true,
				} )
			);

			const machine = createMockMachine( {
				services: {
					createProducts: createProductsMock,
				},
			} );

			const state = machine.getInitialState( initialState );

			const actor = interpret( machine ).start( state );

			await waitFor( actor, ( currentState ) => {
				return currentState.matches(
					'preAssembleSite.preApiCallLoader.installAndActivateTheme.setupStore.createProducts.pending'
				);
			} );

			expect( createProductsMock ).toHaveBeenCalled();

			const finalState = await waitFor( actor, ( currentState ) => {
				return currentState.matches(
					'preAssembleSite.preApiCallLoader.installAndActivateTheme.setupStore.createProducts.success'
				);
			} );

			expect(
				finalState.matches(
					'preAssembleSite.preApiCallLoader.installAndActivateTheme.setupStore.createProducts.success'
				)
			).toBeTruthy();
		} );
	} );
} );
