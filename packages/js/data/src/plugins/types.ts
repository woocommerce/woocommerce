/**
 * Internal dependencies
 */
import { pluginNames } from './constants';
import { WPError } from '../types';

export type RecommendedTypes = 'payments';

export type PluginNames = keyof typeof pluginNames;

export type SelectorKeysWithActions =
	| 'getActivePlugins'
	| 'getInstalledPlugins'
	| 'getRecommendedPlugins'
	| 'installPlugins'
	| 'activatePlugins'
	| 'isJetpackConnected'
	| 'getJetpackConnectionData'
	| 'getJetpackConnectUrl'
	| 'getPaypalOnboardingStatus';

export type PluginsState = {
	active: string[];
	installed: string[];
	requesting: Partial< Record< SelectorKeysWithActions, boolean > >;
	jetpackConnectUrls: Record< string, unknown >;
	jetpackConnection?: boolean;
	jetpackConnectionData?: JetpackConnectionDataResponse;
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

export type PluginsResponse< PluginData > = {
	data: PluginData;
	errors: WPError< Partial< PluginNames > >;
	success: boolean;
	message: string;
} & Response;

export type InstallPluginsResponse = PluginsResponse< {
	installed: string[];
	results: Record< string, boolean >;
	install_time?: Record< string, number >;
	activated: string[];
} >;

export type ActivatePluginsResponse = PluginsResponse< {
	activated: string[];
	active: string[];
} >;

export type JetpackConnectionDataResponse = {
	/** The user on this site who is connected to Jetpack with their WordPress.com account */
	connectionOwner: string | null;
	/** Details about the currently logged in user on this site */
	currentUser: {
		isConnected: boolean;
		isMaster: boolean;
		username: string;
		id: number;
		wpcomUser?: {
			ID?: number;
			login?: string;
			email?: string;
			display_name?: string;
			text_direction?: string;
			site_count?: number;
			jetpack_connect?: string;
			color_scheme?: string;
			sidebar_collapsed?: boolean;
			user_locale?: string;
			avatar?: string;
		};
		gravatar: string;
		permissions: {
			connect: boolean;
			connect_user: boolean;
			disconnect: boolean;
			admin_page: boolean;
			manage_modules: boolean;
			network_admin: boolean;
			network_sites_page: boolean;
			edit_posts: boolean;
			publish_posts: boolean;
			manage_options: boolean;
			view_stats: boolean;
			manage_plugins: boolean;
		};
	};
} & Response;
