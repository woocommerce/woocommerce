export interface PaymentGatewayLink {
	_type: string;
	url: string;
}

export interface PluginData {
	_type?: string;
	slug: string; // The plugin slug (e.g. 'woocommerce'). This is also the directory name of the plugin.
	file: string; // Relative path to the main file of the plugin.
	status: 'installed' | 'active' | 'not_installed';
}

export interface PaymentProviderState {
	enabled: boolean;
	account_connected: boolean;
	needs_setup: boolean;
	test_mode: boolean;
	dev_mode: boolean;
}

export interface LinkData {
	href: string;
}

export interface ManagementData {
	_links: {
		settings: LinkData; // URL to the payment gateway management page.
	};
}

export enum PaymentProviderType {
	OfflinePmsGroup = 'offline_pms_group',
	OfflinePm = 'offline_pm',
	Suggestion = 'suggestion',
	Gateway = 'gateway',
}

export type PaymentIncentive = {
	id: string;
	promo_id: string;
	title: string;
	description: string;
	short_description: string;
	cta_label: string;
	tc_url: string;
	badge: string;
	_dismissals: string[];
	_links: {
		dismiss: LinkData;
	};
};

export type RecommendedPaymentMethod = {
	id: string;
	_order: number;
	title: string;
	description: string;
	icon: string;
	enabled: boolean;
	extraTitle: string;
	extraDescription: string;
	extraIcon: string;
};

export type PaymentProviderOnboardingState = {
	started: boolean;
	completed: boolean;
	test_mode: boolean;
};

// General payment provider type.
export type PaymentProvider = {
	id: string;
	_order: number;
	_type: PaymentProviderType;
	title: string;
	description: string;
	icon: string;
	image?: string;
	supports?: string[];
	plugin: PluginData;
	short_description?: string;
	management?: ManagementData;
	state?: PaymentProviderState;
	links?: PaymentGatewayLink[];
	onboarding?: {
		state: PaymentProviderOnboardingState;
		_links: {
			onboard: LinkData;
		};
		recommended_payment_methods?: RecommendedPaymentMethod[];
	};
	tags?: string[];
	_suggestion_id?: string;
	_links?: Record< string, LinkData >;
	_incentive?: PaymentIncentive;
};

// Payment gateway provider type.
export type PaymentGatewayProvider = PaymentProvider & {
	supports: string[];
	management: ManagementData;
	state: PaymentProviderState;
	onboarding: {
		state: PaymentProviderOnboardingState;
		_links: {
			onboard: LinkData;
		};
		recommended_payment_methods: RecommendedPaymentMethod[];
	};
};

// Offline payment method provider type.
export type OfflinePaymentMethodProvider = PaymentProvider & {
	supports: string[];
	management: ManagementData;
	state: PaymentProviderState;
	onboarding: {
		state: PaymentProviderOnboardingState;
		_links: {
			onboard: LinkData;
		};
	};
};

// Offline payment methods group provider type.
export type OfflinePmsGroupProvider = PaymentProvider & {
	management: ManagementData;
	state: PaymentProviderState;
};

export type PaymentExtensionSuggestionProvider = PaymentProvider & {
	_suggestion_id: string;
	_links: {
		hide: LinkData;
	};
};

// Payment extension suggestion outside providers.
export type SuggestedPaymentExtension = {
	id: string;
	_type: string;
	_priority: number;
	category: string;
	title: string;
	description: string;
	icon: string;
	image: string;
	short_description: string;
	tags: string[];
	plugin: PluginData;
	links: PaymentGatewayLink[];
};

export type SuggestedPaymentExtensionCategory = {
	id: string;
	_priority: number;
	title: string;
	description: string;
};

export type PaymentSettingsState = {
	providers: PaymentProvider[];
	offlinePaymentGateways: OfflinePaymentMethodProvider[];
	suggestions: SuggestedPaymentExtension[];
	suggestionCategories: SuggestedPaymentExtensionCategory[];
	isFetching: boolean;
	errors: Record< string, unknown >;
	isWooPayEligible: boolean;
};

export type OrderMap = Record< string, number >;

export type PaymentProvidersResponse = {
	providers: PaymentProvider[];
	offline_payment_methods: OfflinePaymentMethodProvider[];
	suggestions: SuggestedPaymentExtension[];
	suggestion_categories: SuggestedPaymentExtensionCategory[];
};

export type EnableGatewayResponse = {
	success: boolean;
	data: unknown;
};

export interface WooPayEligibilityResponse {
	is_eligible: boolean;
}
