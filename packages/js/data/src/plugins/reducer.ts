/**
 * External dependencies
 */
import { concat } from 'lodash';

/**
 * Internal dependencies
 */
import { ACTION_TYPES as TYPES } from './action-types';
import { Actions } from './actions';
import { PluginsState } from './types';

const plugins = (
	state: PluginsState = {
		active: [],
		installed: [],
		requesting: {},
		errors: {},
		jetpackConnectUrls: {},
		recommended: {},
	},
	payload?: Actions
): PluginsState => {
	if ( payload && 'type' in payload ) {
		switch ( payload.type ) {
			case TYPES.UPDATE_ACTIVE_PLUGINS:
				state = {
					...state,
					active: payload.replace
						? payload.active
						: ( concat(
								state.active,
								payload.active
						  ) as string[] ),
					requesting: {
						...state.requesting,
						getActivePlugins: false,
						activatePlugins: false,
					},
					errors: {
						...state.errors,
						getActivePlugins: false,
						activatePlugins: false,
					},
				};
				break;
			case TYPES.UPDATE_INSTALLED_PLUGINS:
				state = {
					...state,
					installed: payload.replace
						? payload.installed
						: ( concat(
								state.installed,
								payload.installed
						  ) as string[] ),
					requesting: {
						...state.requesting,
						getInstalledPlugins: false,
						installPlugins: false,
					},
					errors: {
						...state.errors,
						getInstalledPlugins: false,
						installPlugin: false,
					},
				};
				break;
			case TYPES.SET_IS_REQUESTING:
				state = {
					...state,
					requesting: {
						...state.requesting,
						[ payload.selector ]: payload.isRequesting,
					},
				};
				break;
			case TYPES.SET_ERROR:
				state = {
					...state,
					requesting: {
						...state.requesting,
						[ payload.selector ]: false,
					},
					errors: {
						...state.errors,
						[ payload.selector ]: payload.error,
					},
				};
				break;
			case TYPES.UPDATE_JETPACK_CONNECTION:
				state = {
					...state,
					jetpackConnection: payload.jetpackConnection,
				};
				break;
			case TYPES.UPDATE_JETPACK_CONNECT_URL:
				state = {
					...state,
					jetpackConnectUrls: {
						...state.jetpackConnectUrls,
						[ payload.redirectUrl ]: payload.jetpackConnectUrl,
					},
				};
				break;
			case TYPES.SET_PAYPAL_ONBOARDING_STATUS:
				state = {
					...state,
					paypalOnboardingStatus: payload.paypalOnboardingStatus,
				};
				break;
			case TYPES.SET_RECOMMENDED_PLUGINS:
				state = {
					...state,
					recommended: {
						...state.recommended,
						[ payload.recommendedType ]: payload.plugins,
					},
				};
				break;
		}
	}
	return state;
};

export default plugins;
