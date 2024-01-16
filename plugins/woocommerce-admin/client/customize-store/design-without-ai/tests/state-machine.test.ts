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

			interpret( machine ).start();

			expect( hasStepInUrl ).toBeCalled();
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
		} );

		it( "should not transit to installAndActivateTheme state when the url isn't /design", () => {
			const hasStepInUrl = jest.fn( () => false );
			const machine = designWithNoAiStateMachineDefinition.withConfig( {
				guards: {
					hasStepInUrl,
				},
			} );

			const machineInterpret = interpret( machine ).start();

			expect(
				machineInterpret
					.getSnapshot()
					.matches( 'installAndActivateTheme' )
			).toBeFalsy();
		} );
	} );

	describe( 'the preAssembleSite state', () => {
		const initialState = 'preAssembleSite';
		it( 'should start with the pending state', async () => {
			const machine = createMockMachine( {} );

			const actor = interpret( machine ).start( initialState );

			expect(
				actor.getSnapshot().matches( 'preAssembleSite.assembleSite' )
			).toBeTruthy();
		} );

		it( 'should invoke `assembleSite` service', async () => {
			const assembleSiteMock = jest.fn( () => Promise.resolve() );

			const machine = createMockMachine( {
				services: {
					assembleSite: assembleSiteMock,
				},
			} );

			const state = machine.getInitialState( 'preAssembleSite' );

			const actor = interpret( machine );

			const services = actor.start( state );

			await waitFor( services, ( currentState ) =>
				currentState.matches( 'preAssembleSite.assembleSite.pending' )
			);

			expect( assembleSiteMock ).toHaveBeenCalled();
			expect(
				actor.getSnapshot().matches( 'showAssembleHub' )
			).toBeTruthy();
		} );

		it( 'should run `assignAPICallLoaderError` when `assembleSite` service fails', async () => {
			const assembleSiteMock = jest.fn( () => Promise.reject() );
			const assignAPICallLoaderErrorMock = jest.fn();

			const machine = createMockMachine( {
				services: {
					assembleSite: assembleSiteMock,
				},
				actions: {
					assignAPICallLoaderError: assignAPICallLoaderErrorMock,
				},
			} );

			const state = machine.getInitialState( 'preAssembleSite' );

			const actor = interpret( machine );

			const services = actor.start( state );

			await waitFor( services, ( currentState ) =>
				currentState.matches( 'preAssembleSite.assembleSite.pending' )
			);

			expect( assembleSiteMock ).toHaveBeenCalled();
			expect( assignAPICallLoaderErrorMock ).toHaveBeenCalled();
		} );
	} );
} );
