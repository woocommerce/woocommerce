/**
 * External dependencies
 */
import { createActor, fromPromise, waitFor, SimulatedClock } from 'xstate5';

/**
 * Internal dependencies
 */
import { pluginInstallerMachine } from '../installAndActivatePlugins';

describe( 'pluginInstallerMachine', () => {
	const mockConfig = {
		delays: {
			INSTALLATION_TIMEOUT: 1000,
		},
		actions: {
			updateParentWithPluginProgress: jest.fn(),
			updateParentWithInstallationErrors: jest.fn(),
			updateParentWithInstallationSuccess: jest.fn(),
		},
		actors: {
			queueRemainingPluginsAsync: fromPromise( jest.fn() ),
		},
	};

	beforeEach( () => {
		jest.resetAllMocks();
	} );

	it( 'when given one plugin it should call the installPlugin service once', async () => {
		const mockInstallPlugin = jest.fn();
		mockInstallPlugin.mockResolvedValueOnce( {
			data: {
				install_time: {
					'woocommerce-payments': 1000,
				},
			},
		} );

		const mockActors = {
			installPlugin: fromPromise( mockInstallPlugin ),
		};
		const machineUnderTest = pluginInstallerMachine.provide( {
			...mockConfig,
			actors: mockActors,
		} );

		const service = createActor( machineUnderTest, {
			input: {
				selectedPlugins: [ 'woocommerce-payments' ],
				pluginsAvailable: [],
			},
		} ).start();
		await waitFor( service, ( snap ) => snap.matches( 'reportSuccess' ) );

		expect(
			mockConfig.actions.updateParentWithInstallationSuccess.mock
				.calls[ 0 ][ 0 ]
		).toMatchObject( {
			context: {
				installedPlugins: [
					{
						plugin: 'woocommerce-payments',
						installTime: 1000,
					},
				],
			},
		} );

		expect( mockInstallPlugin ).toHaveBeenCalledTimes( 1 );
		expect(
			mockConfig.actions.updateParentWithPluginProgress
		).toHaveBeenCalledTimes( 1 );
	} );

	it( 'when given multiple plugins it should call the installPlugin service the equivalent number of times', async () => {
		const mockInstallPlugin = jest.fn();
		mockInstallPlugin
			.mockResolvedValueOnce( {
				data: {
					install_time: {
						'woocommerce-payments': 1000,
					},
				},
			} )
			.mockResolvedValueOnce( {
				data: {
					install_time: {
						jetpack: 1000,
					},
				},
			} );
		const mockActors = {
			installPlugin: fromPromise( mockInstallPlugin ),
		};
		const machineUnderTest = pluginInstallerMachine.provide( {
			...mockConfig,
			actors: mockActors,
		} );

		const service = createActor( machineUnderTest, {
			input: {
				selectedPlugins: [ 'woocommerce-payments', 'jetpack' ],
				pluginsAvailable: [],
			},
		} ).start();

		await waitFor( service, ( snap ) => snap.matches( 'reportSuccess' ) );

		expect(
			mockConfig.actions.updateParentWithInstallationSuccess.mock
				.calls[ 0 ][ 0 ]
		).toMatchObject( {
			context: {
				installedPlugins: [
					{
						plugin: 'woocommerce-payments',
						installTime: 1000,
					},
					{
						plugin: 'jetpack',
						installTime: 1000,
					},
				],
			},
		} );

		expect( mockInstallPlugin ).toHaveBeenCalledTimes( 2 );

		expect(
			mockConfig.actions.updateParentWithPluginProgress
		).toHaveBeenCalledTimes( 2 );
	} );

	it( 'when a plugin install errors it should report it accordingly', async () => {
		const mockInstallPlugin = jest.fn();
		mockInstallPlugin
			.mockResolvedValueOnce( {
				data: {
					install_time: {
						'woocommerce-payments': 1000,
					},
				},
			} )
			.mockRejectedValueOnce( {
				message: 'error message installing jetpack',
			} );

		const mockActors = {
			installPlugin: fromPromise( mockInstallPlugin ),
		};
		const machineUnderTest = pluginInstallerMachine.provide( {
			...mockConfig,
			actors: mockActors,
		} );

		const service = createActor( machineUnderTest, {
			input: {
				selectedPlugins: [ 'woocommerce-payments', 'jetpack' ],
				pluginsAvailable: [],
			},
		} ).start();

		await waitFor( service, ( snap ) => snap.matches( 'reportErrors' ) );

		expect(
			mockConfig.actions.updateParentWithInstallationErrors.mock
				.calls[ 0 ][ 0 ]
		).toMatchObject( {
			context: {
				installedPlugins: [
					{
						plugin: 'woocommerce-payments',
						installTime: 1000,
					},
				],
				errors: [
					{
						error: 'error message installing jetpack',
						plugin: 'jetpack',
					},
				],
			},
		} );

		expect( mockInstallPlugin ).toHaveBeenCalledTimes( 2 );

		expect(
			mockConfig.actions.updateParentWithPluginProgress
		).toHaveBeenCalledTimes( 2 );
	} );

	it( 'when plugins take longer to install than the timeout, it should queue them async', async () => {
		const clock = new SimulatedClock();
		const mockInstallPlugin = jest.fn();
		mockInstallPlugin
			.mockResolvedValueOnce( {
				data: {
					install_time: {
						'woocommerce-payments': 1000,
					},
				},
			} )
			.mockImplementationOnce( async () => {
				clock.increment( 1500 ); // simulate time passed by 1500ms before this call returns
				return {
					data: {
						install_time: {
							jetpack: 1500,
						},
					},
				};
			} );

		const mockInstallPluginAsync = jest.fn();
		mockInstallPluginAsync.mockResolvedValueOnce( {
			data: {
				job_id: 'foo',
				status: 'pending',
				plugins: [ { status: 'pending', errors: [] } ],
			},
		} );

		const mockActors = {
			installPlugin: fromPromise( mockInstallPlugin ),
			queueRemainingPluginsAsync: fromPromise( mockInstallPluginAsync ),
		};
		const machineUnderTest = pluginInstallerMachine.provide( {
			...mockConfig,
			actors: mockActors,
		} );

		const service = createActor( machineUnderTest, {
			input: {
				selectedPlugins: [
					'woocommerce-payments',
					'jetpack',
					'woocommerce-services',
				],
				pluginsAvailable: [],
			},
			clock,
		} ).start();

		await waitFor( service, ( snap ) => snap.matches( 'reportSuccess' ) );

		expect(
			mockConfig.actions.updateParentWithInstallationSuccess.mock
				.calls[ 0 ][ 0 ]
		).toMatchObject( {
			context: {
				installedPlugins: [
					{
						plugin: 'woocommerce-payments',
						installTime: 1000,
					},
				],
			},
		} );

		expect( mockInstallPlugin ).toHaveBeenCalledTimes( 2 );
		expect(
			mockInstallPluginAsync.mock.calls[ 0 ][ 0 ].input
				.pluginsInstallationQueue
		).toEqual( [ 'jetpack', 'woocommerce-services' ] );

		expect( mockInstallPluginAsync ).toHaveBeenCalledTimes( 1 );
		expect(
			mockInstallPluginAsync.mock.calls[ 0 ][ 0 ].input
				.pluginsInstallationQueue
		).toEqual( [ 'jetpack', 'woocommerce-services' ] );
		expect(
			mockConfig.actions.updateParentWithPluginProgress
		).toHaveBeenCalledTimes( 1 );
		expect(
			mockConfig.actions.updateParentWithPluginProgress.mock
				.calls[ 0 ][ 0 ]
		).toMatchObject( {
			context: {
				installedPlugins: [
					{
						plugin: 'woocommerce-payments',
						installTime: 1000,
					},
				],
			},
		} );
	} );
} );
