export interface PaymentGatewayLink {
	_type: string;
	url: string;
}

// Represents the plugin details for a payment provider.
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
	_dismissals: PaymentIncentiveDismissal[];
	_links: {
		dismiss: LinkData;
	};
};

interface PaymentIncentiveDismissal {
	timestamp: number; // timestamp in seconds
	context: string;
}

export type RecommendedPaymentMethod = {
	id: string;
	_order: number;
	title: string;
	description: string;
	category?: 'primary' | 'secondary';
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
	wpcom_has_working_connection?: boolean;
	wpcom_is_store_connected?: boolean;
	wpcom_has_connected_owner?: boolean;
	wpcom_is_connection_owner?: boolean;
};

// Represents a payments entity, which can be a payment provider or a suggested payment extension outside providers.
export type PaymentsEntity = {
	id: string;
	title: string;
	description: string;
	icon: string;
	plugin: PluginData;
	onboarding?: {
		_links?: {
			preload?: LinkData;
		};
		type?: string;
	};
	_links: Record< string, LinkData >;
};

// Represents a payment provider for the main providers list.
export type PaymentProvider = PaymentsEntity & {
	_type: PaymentProviderType;
	_order: number; // Used for sorting the providers in the UI.
	image?: string;
	supports?: string[];
	management?: ManagementData;
	state?: PaymentProviderState;
	links?: PaymentGatewayLink[];
	onboarding?: {
		state?: PaymentProviderOnboardingState;
		_links?: {
			onboard?: LinkData; // For gateways, this is used to start the onboarding flow.
		};
		recommended_payment_methods?: RecommendedPaymentMethod[];
		type?: string;
	};
	tags?: string[];
	_suggestion_id?: string;
	_incentive?: PaymentIncentive;
};

// Represents a payment gateway in the main providers list.
export type PaymentGatewayProvider = PaymentProvider & {
	_order: number;
	supports: string[];
	management: ManagementData;
	state: PaymentProviderState;
	onboarding: {
		state: PaymentProviderOnboardingState;
		_links: {
			onboard: LinkData;
		};
		recommended_payment_methods: RecommendedPaymentMethod[];
		type: string;
	};
};

// Represents an offline payment method provider in the main providers list.
export type OfflinePaymentMethodProvider = PaymentProvider & {
	_order: number;
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

// Represents an offline payment methods group provider in the main providers list.
export type OfflinePmsGroupProvider = PaymentProvider & {
	_order: number;
	management: ManagementData;
};

// Represents a payment extension suggestion provider in the main providers list.
export type PaymentExtensionSuggestionProvider = PaymentProvider & {
	_order: number;
	onboarding: {
		state: PaymentProviderOnboardingState;
		_links: {
			preload?: LinkData;
		};
		type?: string;
	};
	_suggestion_id: string;
	_links: {
		hide: LinkData;
	};
};

// Represents a suggested payment extension outside the main providers list.
export type SuggestedPaymentExtension = PaymentsEntity & {
	_type: string;
	_priority: number;
	category: string;
	image: string;
	short_description: string;
	tags: string[];
	links: PaymentGatewayLink[];
	_incentive?: PaymentIncentive;
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
