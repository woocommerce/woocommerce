/**
 * External dependencies
 */
import {
	ExtensionList,
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	PluginNames,
} from '@woocommerce/data';
import { dispatch } from '@wordpress/data';
import {
	assign,
	createMachine,
	sendParent,
	actions,
	DoneInvokeEvent,
} from 'xstate';

/**
 * Internal dependencies
 */
import {
	PluginInstalledAndActivatedEvent,
	PluginsInstallationCompletedEvent,
	PluginsInstallationCompletedWithErrorsEvent,
} from '..';

const { pure } = actions;

export type InstalledPlugin = {
	plugin: string;
	installTime: number;
};

export type InstallationCompletedResult = {
	installedPlugins: InstalledPlugin[];
	totalTime: number;
};

export type PluginInstallError = {
	plugin: string;
	error: string;
};

const createInstallationCompletedWithErrorsEvent = (
	errors: PluginInstallError[]
): PluginsInstallationCompletedWithErrorsEvent => ( {
	type: 'PLUGINS_INSTALLATION_COMPLETED_WITH_ERRORS',
	payload: {
		errors,
	},
} );

const createInstallationCompletedEvent = (
	installationCompletedResult: InstallationCompletedResult
): PluginsInstallationCompletedEvent => ( {
	type: 'PLUGINS_INSTALLATION_COMPLETED',
	payload: {
		installationCompletedResult,
	},
} );

const createPluginInstalledAndActivatedEvent = (
	pluginsCount: number,
	installedPluginIndex: number
): PluginInstalledAndActivatedEvent => ( {
	type: 'PLUGIN_INSTALLED_AND_ACTIVATED',
	payload: {
		pluginsCount,
		installedPluginIndex,
	},
} );

export type PluginInstallerMachineContext = {
	selectedPlugins: PluginNames[];
	pluginsAvailable: ExtensionList[ 'plugins' ] | [];
	pluginsInstallationQueue: PluginNames[];
	installedPlugins: InstalledPlugin[];
	startTime: number;
	installationDuration: number;
	errors: PluginInstallError[];
};

type InstallAndActivateErrorResponse = DoneInvokeEvent< {
	error: string;
	message: string;
} >;

type InstallAndActivateSuccessResponse = DoneInvokeEvent< {
	installed: PluginNames[];
	results: Record< PluginNames, boolean >;
	install_time: Record< PluginNames, number >;
} >;

export const pluginInstallerMachine = createMachine(
	{
		id: 'plugin-installer',
		predictableActionArguments: true,
		initial: 'installing',
		context: {
			selectedPlugins: [] as PluginNames[],
			pluginsAvailable: [] as ExtensionList[ 'plugins' ] | [],
			pluginsInstallationQueue: [] as PluginNames[],
			installedPlugins: [] as InstalledPlugin[],
			startTime: 0,
			installationDuration: 0,
			errors: [] as PluginInstallError[],
		} as PluginInstallerMachineContext,
		states: {
			installing: {
				initial: 'installer',
				entry: [
					'populateDefaults',
					'assignPluginsInstallationQueue',
					'assignStartTime',
				],
				after: {
					INSTALLATION_TIMEOUT: 'timedOut',
				},
				states: {
					installer: {
						initial: 'installing',
						states: {
							installing: {
								invoke: {
									src: 'installPlugin',
									onDone: {
										actions: [
											'assignInstallationSuccessDetails',
										],
										target: 'removeFromQueue',
									},
									onError: {
										actions:
											'assignInstallationErrorDetails',
										target: 'removeFromQueue',
									},
								},
							},
							removeFromQueue: {
								entry: [
									'removePluginFromQueue',
									'updateParentWithPluginProgress',
								],
								always: [
									{
										target: 'installing',
										cond: 'hasPluginsToInstall',
									},
									{ target: '#installation-finished' },
								],
							},
						},
					},
				},
			},
			finished: {
				id: 'installation-finished',
				entry: [ 'assignInstallationDuration' ],
				always: [
					{ target: 'reportErrors', cond: 'hasErrors' },
					{ target: 'reportSuccess' },
				],
			},
			timedOut: {
				entry: [ 'assignInstallationDuration' ],
				invoke: {
					src: 'queueRemainingPluginsAsync',
					onDone: {
						target: 'reportSuccess',
					},
				},
			},
			reportErrors: {
				entry: 'updateParentWithInstallationErrors',
			},
			reportSuccess: {
				entry: 'updateParentWithInstallationSuccess',
			},
		},
	},
	{
		delays: {
			INSTALLATION_TIMEOUT: 30000,
		},
		actions: {
			// populateDefaults needed because passing context from the parents overrides the
			// full context object of the child. Not needed in xstatev5 because it
			// becomes a merge
			populateDefaults: assign( {
				installedPlugins: [],
				errors: [],
				startTime: 0,
				installationDuration: 0,
			} ),
			assignPluginsInstallationQueue: assign( {
				pluginsInstallationQueue: ( ctx ) => {
					// Sort the plugins by install_priority so that the smaller plugins are installed first
					// install_priority is set by plugin's size
					// Lower install_prioirty means the plugin is smaller
					return ctx.selectedPlugins.slice().sort( ( a, b ) => {
						const aIndex = ctx.pluginsAvailable.find(
							( plugin ) => plugin.key === a
						);
						const bIndex = ctx.pluginsAvailable.find(
							( plugin ) => plugin.key === b
						);
						return (
							( aIndex?.install_priority ?? 99 ) -
							( bIndex?.install_priority ?? 99 )
						);
					} );
				},
			} ),
			assignStartTime: assign( {
				startTime: () => window.performance.now(),
			} ),
			assignInstallationDuration: assign( {
				installationDuration: ( ctx ) =>
					window.performance.now() - ctx.startTime,
			} ),
			assignInstallationSuccessDetails: assign( {
				installedPlugins: ( ctx, evt ) => {
					const plugin = ctx.pluginsInstallationQueue[ 0 ];
					return [
						...ctx.installedPlugins,
						{
							plugin,
							installTime:
								(
									evt as DoneInvokeEvent< InstallAndActivateSuccessResponse >
								 ).data.data.install_time[ plugin ] || 0,
						},
					];
				},
			} ),
			assignInstallationErrorDetails: assign( {
				errors: ( ctx, evt ) => {
					return [
						...ctx.errors,
						{
							plugin: ctx.pluginsInstallationQueue[ 0 ],
							error: (
								evt as DoneInvokeEvent< InstallAndActivateErrorResponse >
							 ).data.data.message,
						},
					];
				},
			} ),
			removePluginFromQueue: assign( {
				pluginsInstallationQueue: ( ctx ) => {
					return ctx.pluginsInstallationQueue.slice( 1 );
				},
			} ),
			updateParentWithPluginProgress: pure( ( ctx ) =>
				sendParent(
					createPluginInstalledAndActivatedEvent(
						ctx.selectedPlugins.length,
						ctx.selectedPlugins.length -
							ctx.pluginsInstallationQueue.length
					)
				)
			),
			updateParentWithInstallationErrors: sendParent( ( ctx ) =>
				createInstallationCompletedWithErrorsEvent( ctx.errors )
			),
			updateParentWithInstallationSuccess: sendParent( ( ctx ) =>
				createInstallationCompletedEvent( {
					installedPlugins: ctx.installedPlugins,
					totalTime: ctx.installationDuration,
				} )
			),
		},
		guards: {
			hasErrors: ( ctx ) => ctx.errors.length > 0,
			hasPluginsToInstall: ( ctx ) =>
				ctx.pluginsInstallationQueue.length > 0,
		},
		services: {
			installPlugin: ( ctx ) => {
				return dispatch( PLUGINS_STORE_NAME ).installAndActivatePlugins(
					[ ctx.pluginsInstallationQueue[ 0 ] ]
				);
			},
			queueRemainingPluginsAsync: ( ctx ) => {
				return dispatch(
					ONBOARDING_STORE_NAME
				).installAndActivatePluginsAsync(
					ctx.pluginsInstallationQueue
				);
			},
		},
	}
);
