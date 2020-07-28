/**
 * External dependencies
 */
import { controls } from '@wordpress/data-controls';
import { registerStore } from '@wordpress/data';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { without } from 'lodash';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './constants';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';

const { installedExtensions } = getSetting( 'marketing', {} );

const DEFAULT_STATE = {
	installedPlugins: installedExtensions,
	activatingPlugins: [],
	recommendedPlugins: {},
	blogPosts: {},
};

registerStore( STORE_KEY, {
	actions,
	selectors,
	resolvers,
	controls,

	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'SET_INSTALLED_PLUGINS':
				return {
					...state,
					installedPlugins: action.plugins,
				};
			case 'SET_ACTIVATING_PLUGIN':
				return {
					...state,
					activatingPlugins: [
						...state.activatingPlugins,
						action.pluginSlug,
					],
				};
			case 'REMOVE_ACTIVATING_PLUGIN':
				return {
					...state,
					activatingPlugins: without(
						state.activatingPlugins,
						action.pluginSlug
					),
				};
			case 'SET_RECOMMENDED_PLUGINS':
				return {
					...state,
					recommendedPlugins: {
						...state.recommendedPlugins,
						[ action.data.category ]: action.data.plugins,
					},
				};
			case 'SET_BLOG_POSTS':
				return {
					...state,
					blogPosts: {
						...state.blogPosts,
						[ action.data.category ]: action.data.posts,
					},
				};
		}

		return state;
	},
} );
