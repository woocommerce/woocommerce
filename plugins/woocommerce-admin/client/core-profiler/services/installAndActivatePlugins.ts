/**
 * External dependencies
 */
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	PluginNames,
} from '@woocommerce/data';
import { dispatch } from '@wordpress/data';
import { differenceWith } from 'lodash';

/**
 * Internal dependencies
 */
import {
	PluginInstalledAndActivatedEvent,
	PluginsInstallationCompletedEvent,
	PluginsInstallationCompletedWithErrorsEvent,
	CoreProfilerStateMachineContext,
} from '..';
import { getPluginSlug } from '~/utils';

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
	pluginName: string,
	installTime: number,
	installedPluginIndex: number
): PluginInstalledAndActivatedEvent => ( {
	type: 'PLUGIN_INSTALLED_AND_ACTIVATED',
	payload: {
		pluginName,
		installTime,
		installedPluginIndex,
	},
} );

export const InstallAndActivatePlugins =
	( context: CoreProfilerStateMachineContext ) =>
	(
		callback: (
			event:
				| PluginInstalledAndActivatedEvent
				| PluginsInstallationCompletedEvent
				| PluginsInstallationCompletedWithErrorsEvent
		) => void,
		onReceive: ( callback: ( event: { type: string } ) => void ) => void
	) => {
		( async () => {
			let installationTimedOut = false;
			const installationStartTime = window.performance.now();
			const setInstallationCompletedTime = () => {
				context.pluginsInstallationCompleted.totalTime =
					window.performance.now() - installationStartTime;
			};

			// Called when the installation times out via `after`
			onReceive( ( event: { type: string } ) => {
				if ( event.type === 'PLUGINS_INSTALLATION_TIMED_OUT' ) {
					installationTimedOut = true;
				}
			} );

			const handleInstallationCompleted = () => {
				if ( context.pluginsInstallationErrors.length ) {
					return callback(
						createInstallationCompletedWithErrorsEvent(
							context.pluginsInstallationErrors
						)
					);
				}
				setInstallationCompletedTime();
				callback(
					createInstallationCompletedEvent(
						context.pluginsInstallationCompleted
					)
				);
			};

			const handlePluginInstalledAndActivated = (
				pluginName: string,
				installTime: number,
				installedPluginIndex: number
			) => {
				callback(
					createPluginInstalledAndActivatedEvent(
						pluginName,
						installTime,
						installedPluginIndex
					)
				);
			};

			const handlePluginInstallError = (
				plugin: string,
				error: unknown
			) => {
				context.pluginsInstallationErrors.push( {
					plugin,
					error:
						error instanceof Error
							? error.message
							: String( error ),
				} );
			};

			const handlePluginInstallation = async (
				installedPluginIndex: number
			) => {
				const plugin = getPluginSlug(
					context.pluginsSelected[ installedPluginIndex ]
				);

				try {
					const response = await dispatch(
						PLUGINS_STORE_NAME
					).installAndActivatePlugins( [ plugin ] );

					handlePluginInstalledAndActivated(
						plugin,
						response.data?.install_time?.[ plugin ] || 0,
						installedPluginIndex
					);
				} catch ( error ) {
					handlePluginInstallError( plugin, error );
				}
			};

			for (
				let index = 0;
				index < context.pluginsSelected.length;
				index++
			) {
				if ( installationTimedOut ) {
					return;
				}
				await handlePluginInstallation( index );
			}

			handleInstallationCompleted();
		} )();
	};

export const InstallAndActivatePluginsTimedOut = (
	context: CoreProfilerStateMachineContext
) => {
	const remainingPlugins = differenceWith(
		context.pluginsSelected,
		context.pluginsInstallationCompleted.installedPlugins.map(
			( plugin ) => plugin.plugin
		)
	);

	return dispatch( ONBOARDING_STORE_NAME ).installAndActivatePluginsAsync(
		remainingPlugins as PluginNames[]
	);
};
