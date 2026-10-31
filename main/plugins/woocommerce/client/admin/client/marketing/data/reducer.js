/**
 * External dependencies
 */
import { without } from 'lodash';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { getAdminSetting } from '~/utils/admin-settings';

const { installedExtensions } = getAdminSetting( 'marketing', {} );

const DEFAULT_STATE = {
	installedPlugins: installedExtensions,
	activatingPlugins: [],
	recommendedPlugins: {},
	miscRecommendations: [],
	blogPosts: {},
	errors: {},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case TYPES.SET_INSTALLED_PLUGINS:
			return {
				...state,
				installedPlugins: action.plugins,
			};
		case TYPES.SET_ACTIVATING_PLUGIN:
			return {
				...state,
				activatingPlugins: [
					...state.activatingPlugins,
					action.pluginSlug,
				],
			};
		case TYPES.REMOVE_ACTIVATING_PLUGIN:
			return {
				...state,
				activatingPlugins: without(
					state.activatingPlugins,
					action.pluginSlug
				),
			};
		case TYPES.SET_RECOMMENDED_PLUGINS:
			return {
				...state,
				recommendedPlugins: {
					...state.recommendedPlugins,
					[ action.data.category ]: action.data.plugins,
				},
			};
		case TYPES.INSTALL_AND_ACTIVATE_RECOMMENDED_PLUGIN:
			const newPlugins = state.recommendedPlugins[
				action.data.category
			]?.filter(
				( plugin ) => plugin.product !== action.data.pluginSlug
			);

			return {
				...state,
				recommendedPlugins: {
					...state.recommendedPlugins,
					[ action.data.category ]: newPlugins,
				},
			};
		case TYPES.SET_BLOG_POSTS:
			return {
				...state,
				blogPosts: {
					...state.blogPosts,
					[ action.data.category ]: action.data.posts,
				},
			};
		case TYPES.SET_MISC_RECOMMENDATIONS:
			return {
				...state,
				miscRecommendations: action.data.miscRecommendations,
			};
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					blogPosts: {
						...state.errors.blogPosts,
						[ action.category ]: action.error,
					},
				},
			};
		default:
			return state;
	}
};

export default reducer;
