/**
 * External dependencies
 */
import { getPluginSlug } from '~/utils';

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
} from '..';
import { CoreProfilerStateMachineContext } from '..';

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
): PluginsInstallationCompletedWithErrorsEvent => {
	return {
		type: 'PLUGINS_INSTALLATION_COMPLETED_WITH_ERRORS',
		payload: {
			errors,
		},
	};
};

const createInstallationCompletedEvent = (
	installationCompletedResult: InstallationCompletedResult
): PluginsInstallationCompletedEvent => {
	return {
		type: 'PLUGINS_INSTALLATION_COMPLETED',
		payload: {
			installationCompletedResult,
		},
	};
};

const createPluginInstalledAndActivatedEvent = (
	pluginsCount: number,
	installedPluginIndex: number
): PluginInstalledAndActivatedEvent => {
	return {
		type: 'PLUGIN_INSTALLED_AND_ACTIVATED',
		payload: {
			pluginsCount,
			installedPluginIndex,
		},
	};
};

export const InstallAndActivatePlugins = (
	context: CoreProfilerStateMachineContext
) => async (
	send: (
		event:
			| PluginInstalledAndActivatedEvent
			| PluginsInstallationCompletedEvent
			| PluginsInstallationCompletedWithErrorsEvent
	) => void
) => {
	let continueInstallation = true;
	let pluginIndex = 0;
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

	/**
	 * When the timer is up, stop the installation
	 * Reject the promise with errors if any
	 * Otherwise, queue the remaining plugins to the action scheduler and resolve.
	 */
	let timer = setTimeout( async () => {
		continueInstallation = false;

		if ( errors.length > 0 ) {
			return send( createInstallationCompletedWithErrorsEvent( errors ) );
		}

		const remainingPlugins = differenceWith(
			context.pluginsSelected,
			installationCompletedResult.installedPlugins.map(
				( plugin ) => plugin.plugin
			)
		);

		// todo: replace it with onboarding/install-and-activate-async endpoint
		await dispatch( PLUGINS_STORE_NAME ).installPlugins(
			remainingPlugins as PluginNames[],
			true
		);

		setInstallationCompletedTime();
		return send(
			createInstallationCompletedEvent( installationCompletedResult )
		);
		// todo: grab job_id from onboarding/install-and-activate and save it to cookie
		// so that we can reference errors later.
	}, 1000 * 30 );

	/**
	 * Install plugins one by one in sync
	 */
	while ( continueInstallation ) {
		const plugin = getPluginSlug( context.pluginsSelected[ pluginIndex ] );

		try {
			const response = await dispatch(
				PLUGINS_STORE_NAME
			).installAndActivatePlugins( [ plugin ] );

			installationCompletedResult.installedPlugins.push( {
				plugin,
				installTime: response.data?.install_time?.[ plugin ] || 0,
			} );

			send(
				createPluginInstalledAndActivatedEvent(
					context.pluginsSelected.length,
					pluginIndex + 1
				)
			);
		} catch ( error ) {
			errors.push( {
				plugin,
				error: error instanceof Error ? error.message : String( error ),
			} );
		}

		if ( context.pluginsSelected.length - 1 > pluginIndex ) {
			pluginIndex++;
		} else {
			/**
			 * When everything is done, stop the installation
			 * Reject the promise with errors if any
			 * Otherwise, resolve.
			 */
			continueInstallation = false;
			if ( errors.length ) {
				return send(
					createInstallationCompletedWithErrorsEvent( errors )
				);
			}

			setInstallationCompletedTime();
			clearTimeout( timer );
			return send(
				createInstallationCompletedEvent( installationCompletedResult )
			);
		}
	}
};
