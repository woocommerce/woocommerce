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
