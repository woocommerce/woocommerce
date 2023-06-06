/**
 * External dependencies
 */
import { PLUGINS_STORE_NAME, PluginNames } from '@woocommerce/data';
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
	pluginsCount: number,
	installedPluginIndex: number
): PluginInstalledAndActivatedEvent => ( {
	type: 'PLUGIN_INSTALLED_AND_ACTIVATED',
	payload: {
		pluginsCount,
		installedPluginIndex,
	},
} );

export const InstallAndActivatePlugins =
	( context: CoreProfilerStateMachineContext ) =>
	async (
		send: (
			event:
				| PluginInstalledAndActivatedEvent
				| PluginsInstallationCompletedEvent
				| PluginsInstallationCompletedWithErrorsEvent
		) => void
	) => {
		let continueInstallation = true;
		const errors: PluginInstallError[] = [];
		const installationCompletedResult: InstallationCompletedResult = {
			installedPlugins: [],
			totalTime: 0,
		};
		const installationStartTime = window.performance.now();
		const setInstallationCompletedTime = () => {
			installationCompletedResult.totalTime =
				window.performance.now() - installationStartTime;
		};

		// Sort selected plugins by install_priority.
		const sortedPlugins = context.pluginsSelected
			.slice()
			.sort( ( a, b ) => {
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

		const handleInstallationCompleted = () => {
			if ( errors.length ) {
				return send(
					createInstallationCompletedWithErrorsEvent( errors )
				);
			}
			setInstallationCompletedTime();
			send(
				createInstallationCompletedEvent( installationCompletedResult )
			);
		};

		const handlePluginInstalledAndActivated = (
			installedPluginIndex: number
		) => {
			send(
				createPluginInstalledAndActivatedEvent(
					context.pluginsSelected.length,
					installedPluginIndex + 1
				)
			);
		};

		const handlePluginInstallError = ( plugin: string, error: unknown ) => {
			errors.push( {
				plugin,
				error: error instanceof Error ? error.message : String( error ),
			} );
		};

		const handlePluginInstallation = async (
			installedPluginIndex: number
		) => {
			// Set by timer when it's up
			if ( ! continueInstallation ) {
				return;
			}

			const plugin = getPluginSlug(
				sortedPlugins[ installedPluginIndex ]
			);
			try {
				const response = await dispatch(
					PLUGINS_STORE_NAME
				).installAndActivatePlugins( [ plugin ] );

				installationCompletedResult.installedPlugins.push( {
					plugin,
					installTime: response.data?.install_time?.[ plugin ] || 0,
				} );

				handlePluginInstalledAndActivated( installedPluginIndex );
			} catch ( error ) {
				handlePluginInstallError( plugin, error );
			}
		};

		const handleTimerExpired = async () => {
			continueInstallation = false;

			const remainingPlugins = differenceWith(
				context.pluginsSelected,
				installationCompletedResult.installedPlugins.map(
					( plugin ) => plugin.plugin
				)
			);

			await dispatch( PLUGINS_STORE_NAME ).installPlugins(
				remainingPlugins as PluginNames[],
				true
			);

			handleInstallationCompleted();
		};

		const timer = setTimeout( handleTimerExpired, 1000 * 30 );

		for ( let index = 0; index < sortedPlugins.length; index++ ) {
			await handlePluginInstallation( index );
		}

		clearTimeout( timer );
		handleInstallationCompleted();
	};
