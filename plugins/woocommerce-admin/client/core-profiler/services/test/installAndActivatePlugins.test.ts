/**
 * External dependencies
 */
import { PluginNames } from '@woocommerce/data';
import { interpret } from 'xstate';

/**
 * Internal dependencies
 */
import {
	pluginInstallerMachine,
	InstalledPlugin,
	PluginInstallError,
} from '../installAndActivatePlugins';

describe( 'pluginInstallerMachine', () => {
	const dispatchInstallPluginMock = jest.fn();
	const defaultContext = {
		selectedPlugins: [] as PluginNames[],
		pluginsInstallationQueue: [] as PluginNames[],
		installedPlugins: [] as InstalledPlugin[],
		startTime: 0,
		installationDuration: 0,
		errors: [] as PluginInstallError[],
	};
	const mockConfig = {
		delays: {
			INSTALLATION_DELAY: 1000,
		},
		actions: {
			updateParentWithPluginProgress: jest.fn(),
			updateParentWithInstallationErrors: jest.fn(),
			updateParentWithInstallationSuccess: jest.fn(),
		},
		services: {
			installPlugin: dispatchInstallPluginMock,
			queueRemainingPluginsAsync: jest.fn(),
		},
	};

	beforeEach( () => {
		jest.resetAllMocks();
	} );

	it( 'when given one plugin it should call the installPlugin service once', ( done ) => {
		const machineUnderTest = pluginInstallerMachine
			.withConfig( mockConfig )
			.withContext( {
				...defaultContext,
				selectedPlugins: [ 'woocommerce-payments' ],
				pluginsAvailable: [],
			} );

		dispatchInstallPluginMock.mockImplementationOnce( ( context ) => {
			expect( context.pluginsInstallationQueue ).toEqual( [
				'woocommerce-payments',
			] );
			return Promise.resolve( {
				data: {
					install_time: {
						'woocommerce-payments': 1000,
					},
				},
			} );
		} );

		const service = interpret( machineUnderTest ).start();
		service.onTransition( ( state ) => {
			if ( state.matches( 'reportSuccess' ) ) {
				expect(
					mockConfig.actions.updateParentWithInstallationSuccess
				).toHaveBeenCalledWith(
					// context param
					expect.objectContaining( {
						installedPlugins: [
							{
								plugin: 'woocommerce-payments',
								installTime: 1000,
							},
						],
					} ),
					// event param
					expect.any( Object ),
					// meta param
					expect.any( Object )
				);
				done();
			}
		} );
	} );
} );

// TODO: write more tests, I ran out of time and it's friday night
// we need tests for:
// 1. when given multiple plugins it should call the installPlugin service multiple times with the right plugins
// 2. when given multiple plugins and a mocked delay using the config, we can mock the installs to take longer than the timeout and then some plugins should not finish installing, then it should add the remaining to async queue
// 3. when a plugin gives an error it should report the error to the parents. we can check this by mocking 'updateParentWithInstallationErrors'
// 4. it should update parent with the plugin installation progress, we can check this by mocking the action 'updateParentWithPluginProgress'
