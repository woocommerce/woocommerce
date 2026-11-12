/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { PluginNames, pluginsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '../lib/notices';

export const useInstallPlugin = () => {
	const [ pluginsBeingSetup, setPluginsBeingSetup ] = useState<
		Array< string >
	>( [] );

	const { installPlugins, activatePlugins } = useDispatch( pluginsStore );

	const handleInstall = ( slugs: string[] ): PromiseLike< void > => {
		if ( pluginsBeingSetup.length > 0 ) {
			return Promise.resolve();
		}

		setPluginsBeingSetup( slugs );

		return installPlugins( slugs as Partial< PluginNames >[] )
			.then( () => {
				setPluginsBeingSetup( [] );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setPluginsBeingSetup( [] );

				return Promise.reject();
			} );
	};

	const handleActivate = ( slugs: string[] ): PromiseLike< void > => {
		if ( pluginsBeingSetup.length > 0 ) {
			return Promise.resolve();
		}

		setPluginsBeingSetup( slugs );

		return activatePlugins( slugs as Partial< PluginNames >[] )
			.then( () => {
				setPluginsBeingSetup( [] );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setPluginsBeingSetup( [] );

				return Promise.reject();
			} );
	};

	return [ pluginsBeingSetup, handleInstall, handleActivate ] as const;
};
