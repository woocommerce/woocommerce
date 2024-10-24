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
	DoneActorEvent,
	ErrorActorEvent,
	assign,
	createMachine,
	fromPromise,
	sendParent,
} from 'xstate5';

/**
 * Internal dependencies
 */
import {
	PluginInstalledAndActivatedEvent,
	PluginsInstallationCompletedEvent,
	PluginsInstallationCompletedWithErrorsEvent,
} from '../events';

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
	errorDetails: Pick< InstallAndActivateErrorResponse, 'data' >;
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
	progressPercentage: number
): PluginInstalledAndActivatedEvent => ( {
	type: 'PLUGIN_INSTALLED_AND_ACTIVATED',
	payload: {
		progressPercentage,
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

export type InstallAndActivateErrorResponse = {
	error: string;
	message: string;
	data: {
		code: string | 'woocommerce_rest_cannot_update';
		data: {
			status: number;
		};
	};
};

type InstallAndActivateSuccessResponse = {
	data: {
		installed: PluginNames[];
		results: Record< PluginNames, boolean >;
		install_time: Record< PluginNames, number >;
	};
};

export const INSTALLATION_TIMEOUT = 30000; // milliseconds; queue remaining plugin installations to async after  this timeout

export const pluginInstallerMachine = createMachine(
	{
		id: 'plugin-installer',
		initial: 'installing',
		types: {} as {
			context: PluginInstallerMachineContext;
		},
		context: ( {
			input,
		}: {
			input: Pick<
				PluginInstallerMachineContext,
				'selectedPlugins' | 'pluginsAvailable'
			>;
		} ) => {
			return {
				selectedPlugins:
					input?.selectedPlugins || ( [] as PluginNames[] ),
				pluginsAvailable:
					input?.pluginsAvailable ||
					( [] as ExtensionList[ 'plugins' ] | [] ),
				pluginsInstallationQueue: [] as PluginNames[],
				installedPlugins: [] as InstalledPlugin[],
				startTime: 0,
				installationDuration: 0,
				errors: [] as PluginInstallError[],
			} as PluginInstallerMachineContext;
		},
		states: {
			installing: {
				initial: 'installer',
				entry: [ 'assignPluginsInstallationQueue', 'assignStartTime' ],
				after: {
					INSTALLATION_TIMEOUT: 'timedOut',
				},
				states: {
					installer: {
						initial: 'installing',
						states: {
							installing: {
								invoke: {
									systemId: 'installPlugin',
									src: 'installPlugin',
									input: ( { context } ) => context,
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
										guard: 'hasPluginsToInstall',
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
					{ target: 'reportErrors', guard: 'hasErrors' },
					{ target: 'reportSuccess' },
				],
			},
			timedOut: {
				entry: [ 'assignInstallationDuration' ],
				invoke: {
					systemId: 'queueRemainingPluginsAsync',
					src: 'queueRemainingPluginsAsync',
					input: ( { context } ) => ( {
						pluginsInstallationQueue:
							context.pluginsInstallationQueue,
					} ),
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
			INSTALLATION_TIMEOUT,
		},
		actions: {
			assignPluginsInstallationQueue: assign( {
				pluginsInstallationQueue: ( { context } ) => {
					// Sort the plugins by install_priority so that the smaller plugins are installed first
					// install_priority is set by plugin's size
					// Lower install_prioirty means the plugin is smaller
					return context.selectedPlugins.slice().sort( ( a, b ) => {
						const aIndex = context.pluginsAvailable.find(
							( plugin ) => plugin.key === a
						);
						const bIndex = context.pluginsAvailable.find(
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
				installationDuration: ( { context } ) =>
					window.performance.now() - context.startTime,
			} ),
			assignInstallationSuccessDetails: assign( {
				installedPlugins: ( { context, event } ) => {
					const plugin = context.pluginsInstallationQueue[ 0 ];
					return [
						...context.installedPlugins,
						{
							plugin,
							installTime:
								(
									event as DoneActorEvent< InstallAndActivateSuccessResponse >
								 ).output.data.install_time[ plugin ] || 0,
						},
					];
				},
			} ),
			assignInstallationErrorDetails: assign( {
				errors: ( { context, event } ) => {
					return [
						...context.errors,
						{
							plugin: context.pluginsInstallationQueue[ 0 ],
							error: (
								event as ErrorActorEvent< InstallAndActivateErrorResponse >
							 ).error.message,
							errorDetails: (
								event as ErrorActorEvent< InstallAndActivateErrorResponse >
							 ).error,
						},
					];
				},
			} ),
			removePluginFromQueue: assign( {
				pluginsInstallationQueue: ( { context } ) => {
					return context.pluginsInstallationQueue.slice( 1 );
				},
			} ),
			updateParentWithPluginProgress: sendParent( ( { context } ) => {
				const installedCount =
					context.selectedPlugins.length -
					context.pluginsInstallationQueue.length;
				const pluginsToInstallCount = context.selectedPlugins.length;

				const percentageOfPluginsInstalled = Math.round(
					( installedCount / pluginsToInstallCount ) * 100
				);

				const elapsed = window.performance.now() - context.startTime;
				const percentageOfTimePassed = Math.round(
					( elapsed / INSTALLATION_TIMEOUT ) * 100
				);

				return createPluginInstalledAndActivatedEvent(
					Math.max(
						percentageOfPluginsInstalled,
						percentageOfTimePassed
					)
				);
			} ),
			updateParentWithInstallationErrors: sendParent( ( { context } ) =>
				createInstallationCompletedWithErrorsEvent( context.errors )
			),
			updateParentWithInstallationSuccess: sendParent( ( { context } ) =>
				createInstallationCompletedEvent( {
					installedPlugins: context.installedPlugins,
					totalTime: context.installationDuration,
				} )
			),
		},
		guards: {
			hasErrors: ( { context } ) => context.errors.length > 0,
			hasPluginsToInstall: ( { context } ) =>
				context.pluginsInstallationQueue.length > 0,
		},
		actors: {
			installPlugin: fromPromise(
				async ( {
					input: { pluginsInstallationQueue },
				}: {
					input: { pluginsInstallationQueue: PluginNames[] };
				} ) => {
					return dispatch(
						PLUGINS_STORE_NAME
					).installAndActivatePlugins( [
						pluginsInstallationQueue[ 0 ],
					] );
				}
			),
			queueRemainingPluginsAsync: fromPromise(
				async ( {
					input: { pluginsInstallationQueue },
				}: {
					input: { pluginsInstallationQueue: PluginNames[] };
				} ) => {
					return dispatch(
						ONBOARDING_STORE_NAME
					).installAndActivatePluginsAsync(
						pluginsInstallationQueue
					);
				}
			),
		},
	}
);
