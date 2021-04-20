/**
 * Internal dependencies
 */
import { WPDataSelector, WPDataSelectors } from '../types';
import {
	PluginsState,
	RecommendedTypes,
	SelectorKeysWithActions,
} from './types';

export const getActivePlugins = ( state: PluginsState ) => {
	return state.active || [];
};

export const getInstalledPlugins = ( state: PluginsState ) => {
	return state.installed || [];
};

export const isPluginsRequesting = (
	state: PluginsState,
	selector: SelectorKeysWithActions
) => {
	return state.requesting[ selector ] || false;
};

export const getPluginsError = (
	state: PluginsState,
	selector: SelectorKeysWithActions
) => {
	return state.errors[ selector ] || false;
};

export const isJetpackConnected = ( state: PluginsState ) =>
	state.jetpackConnection;

export const getJetpackConnectUrl = (
	state: PluginsState,
	query: { redirect_url: string }
) => {
	return state.jetpackConnectUrls[ query.redirect_url ];
};

export const getPluginInstallState = (
	state: PluginsState,
	plugin: string
) => {
	if ( state.active.includes( plugin ) ) {
		return 'activated';
	} else if ( state.installed.includes( plugin ) ) {
		return 'installed';
	}

	return 'unavailable';
};

export const getPaypalOnboardingStatus = ( state: PluginsState ) =>
	state.paypalOnboardingStatus;

export const getRecommendedPlugins = (
	state: PluginsState,
	type: RecommendedTypes
) => {
	return state.recommended[ type ];
};

// Types
export type PluginSelectors = {
	getActivePlugins: WPDataSelector< typeof getActivePlugins >;
	getInstalledPlugins: WPDataSelector< typeof getInstalledPlugins >;
	getRecommendedPlugins: WPDataSelector< typeof getRecommendedPlugins >;
} & WPDataSelectors;
