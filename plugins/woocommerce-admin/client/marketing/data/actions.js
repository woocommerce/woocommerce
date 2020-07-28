/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';
import { __ } from '@wordpress/i18n';

export function receiveInstalledPlugins( plugins ) {
	return {
		type: 'SET_INSTALLED_PLUGINS',
		plugins,
	};
}

export function receiveActivatingPlugin( pluginSlug ) {
	return {
		type: 'SET_ACTIVATING_PLUGIN',
		pluginSlug,
	};
}

export function removeActivatingPlugin( pluginSlug ) {
	return {
		type: 'REMOVE_ACTIVATING_PLUGIN',
		pluginSlug,
	};
}

export function receiveRecommendedPlugins( plugins, category ) {
	return {
		type: 'SET_RECOMMENDED_PLUGINS',
		data: { plugins, category },
	};
}

export function receiveBlogPosts( posts, category ) {
	return {
		type: 'SET_BLOG_POSTS',
		data: { posts, category },
	};
}

export function handleFetchError( error, message ) {
	const { createNotice } = dispatch( 'core/notices' );
	createNotice( 'error', message );

	// eslint-disable-next-line no-console
	console.log( error );
}

export function* activateInstalledPlugin( pluginSlug ) {
	const { createNotice } = dispatch( 'core/notices' );
	yield receiveActivatingPlugin( pluginSlug );

	try {
		const response = yield apiFetch( {
			path: API_NAMESPACE + '/overview/activate-plugin',
			method: 'POST',
			data: {
				plugin: pluginSlug,
			},
		} );

		if ( response ) {
			yield createNotice(
				'success',
				__(
					'The extension has been successfully activated.',
					'woocommerce-admin'
				)
			);
			// Deliberately load the new plugin data in a new request.
			yield loadInstalledPluginsAfterActivation( pluginSlug );
		} else {
			throw new Error();
		}
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to activate the extension.',
				'woocommerce-admin'
			)
		);
		yield removeActivatingPlugin( pluginSlug );
	}

	return true;
}

export function* loadInstalledPluginsAfterActivation( activatedPluginSlug ) {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/overview/installed-plugins`,
		} );

		if ( response ) {
			yield receiveInstalledPlugins( response );
			yield removeActivatingPlugin( activatedPluginSlug );
		} else {
			throw new Error();
		}
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading installed extensions.',
				'woocommerce-admin'
			)
		);
	}
}
