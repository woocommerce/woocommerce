/**
 * External dependencies
 */
import { PLUGINS_STORE_NAME, PluginNames } from '@woocommerce/data';
import { dispatch } from '@wordpress/data';
import { differenceWith } from 'lodash';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '..';

export const InstallAndActivatePlugins = async (
	context: CoreProfilerStateMachineContext
) => {
	return new Promise( async ( resolve, reject ) => {
		let continueInstallation = true;
		let pluginIndex = 0;
		const errors: { plugin: string; error: string }[] = [];
		const installedPlugins: string[] = [];

		const testFailure = () => {
			continueInstallation = false;
			errors.push( {
				plugin: 'jetpack',
				error: 'This is a test error',
			} );
			errors.push( {
				plugin: 'woocommerce-payments',
				error: 'This is a test error',
			} );
			reject( errors );
		};

		testFailure();

		/**
		 * When the timer is up, stop the installation
		 * Reject the promise with errors if any
		 * Otherwise, queue the remaining plugins to the action scheduler.
		 */
		setTimeout( async () => {
			console.log( 'time is up' );

			if ( errors.length > 0 ) {
				return reject( errors );
			}

			continueInstallation = false;
			const remainingPlugins = differenceWith(
				context.extensionsSelected,
				installedPlugins
			);
			console.log( 'remaining plugins', remainingPlugins );

			// todo: replace it with onboarding/install-and-activate-async endpoint
			await dispatch( PLUGINS_STORE_NAME ).installPlugins(
				remainingPlugins as PluginNames[],
				true
			);
			return resolve( true );
			// todo: grab job_id from onboarding/install-and-activate and save it to cookie
			// so that we can reference any errors later.
		}, 1000 * 900 );

		/**
		 * Install plugins one by one in sync
		 */
		while ( continueInstallation ) {
			const plugin = context.extensionsSelected[ pluginIndex ];

			try {
				console.log( 'Installing ', plugin );

				await dispatch(
					PLUGINS_STORE_NAME
				).installAndActivatePlugins( [ plugin ] );

				installedPlugins.push( plugin );
			} catch ( error ) {
				errors.push( {
					plugin,
					error:
						error instanceof Error
							? error.message
							: String( error ),
				} );
			}

			if ( context.extensionsSelected.length - 1 > pluginIndex ) {
				pluginIndex++;
			} else {
				errors.length > 0 ? reject( errors ) : resolve( true );
				continueInstallation = false;
			}
		}

		return resolve( true );
	} );
};
