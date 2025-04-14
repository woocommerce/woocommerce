/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';

export function receiveInstalledPlugins( plugins ) {
	return {
		type: TYPES.SET_INSTALLED_PLUGINS,
		plugins,
	};
}

export function receiveActivatingPlugin( pluginSlug ) {
	return {
		type: TYPES.SET_ACTIVATING_PLUGIN,
		pluginSlug,
	};
}

export function removeActivatingPlugin( pluginSlug ) {
	return {
		type: TYPES.REMOVE_ACTIVATING_PLUGIN,
		pluginSlug,
	};
}

export function receiveRecommendedPlugins( plugins, category ) {
	return {
		type: TYPES.SET_RECOMMENDED_PLUGINS,
		data: { plugins, category },
	};
}

export function receiveMiscRecommendations( miscRecommendations ) {
	return {
		type: TYPES.SET_MISC_RECOMMENDATIONS,
		data: { miscRecommendations },
	};
}

export function receiveBlogPosts( posts, category ) {
	return {
		type: TYPES.SET_BLOG_POSTS,
		data: { posts, category },
	};
}

export function handleFetchError( error, message ) {
	const { createNotice } = dispatch( 'core/notices' );
	createNotice( 'error', message );

	// eslint-disable-next-line no-console
	console.log( error );
}

export function setError( category, error ) {
	return {
		type: TYPES.SET_ERROR,
		category,
		error,
	};
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
				'woocommerce'
			)
		);
	}
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
					'woocommerce'
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
				'woocommerce'
			)
		);
		yield removeActivatingPlugin( pluginSlug );
	}

	return true;
}

export function* installAndActivateRecommendedPlugin(
	recommendedPluginSlug,
	category
) {
	return {
		type: TYPES.INSTALL_AND_ACTIVATE_RECOMMENDED_PLUGIN,
		data: { pluginSlug: recommendedPluginSlug, category },
	};
}
