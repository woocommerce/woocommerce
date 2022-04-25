/**
 * Internal dependencies
 */
import { pluginNames } from './constants';

export type RecommendedTypes = 'payments';

export type PluginNames = keyof typeof pluginNames;

export type SelectorKeysWithActions =
	| 'getActivePlugins'
	| 'getInstalledPlugins'
	| 'getRecommendedPlugins'
	| 'installPlugins'
	| 'activatePlugins'
	| 'isJetpackConnected'
	| 'getJetpackConnectUrl'
	| 'getPaypalOnboardingStatus';

export type PluginsState = {
	active: string[];
	installed: string[];
	requesting: Partial< Record< SelectorKeysWithActions, boolean > >;
	jetpackConnectUrls: Record< string, unknown >;
	jetpackConnection?: boolean;
	recommended: Partial< Record< RecommendedTypes, Plugin[] > >;
	paypalOnboardingStatus?: Partial< PaypalOnboardingStatus >;
	// TODO clarify what the error record's type is
	errors: Record< string, unknown >;
};

export type Plugin = {
	id: string;
	content: string;
	plugins: string[];
	title: string;
	category_additional: string[];
	category_other: string[];
	image: string;
	image_72x72?: string;
	square_image?: string;
	recommendation_priority?: number;
	is_visible?: boolean;
	is_local_partner?: boolean;
	is_offline?: boolean;
	actionText?: string;
	recommended?: boolean;
};

type PaypalOnboardingState = 'unknown' | 'start' | 'progressive' | 'onboarded';
export type PaypalOnboardingStatus = {
	environment: string;
	onboarded: boolean;
	state: PaypalOnboardingState;
	sandbox: {
		state: PaypalOnboardingState;
		onboarded: boolean;
	};
	production: {
		state: PaypalOnboardingState;
		onboarded: boolean;
	};
};
