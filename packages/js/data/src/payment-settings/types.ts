export interface PaymentsProviderLink {
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

export interface PaymentsProviderState {
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

export enum PaymentsProviderType {
	OfflinePmsGroup = 'offline_pms_group',
	OfflinePm = 'offline_pm',
	Suggestion = 'suggestion',
	Gateway = 'gateway',
}

export type PaymentsProviderIncentive = {
	id: string;
	promo_id: string;
	title: string;
	description: string;
	short_description: string;
	cta_label: string;
	tc_url: string;
	badge: string;
	_dismissals: PaymentsProviderIncentiveDismissal[];
	_links: {
		dismiss: LinkData;
	};
};

interface PaymentsProviderIncentiveDismissal {
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

export type PaymentsProviderOnboardingState = {
	supported: boolean;
	started: boolean;
	completed: boolean;
	test_mode: boolean;
	test_drive_account?: boolean;
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
	_suggestion_id?: string;
};

// Represents a payments provider for the main providers list.
export type PaymentsProvider = PaymentsEntity & {
	_type: PaymentsProviderType;
	_order: number; // Used for sorting the providers in the UI.
	image?: string;
	supports?: string[];
	management?: ManagementData;
	state?: PaymentsProviderState;
	links?: PaymentsProviderLink[];
	onboarding?: {
		state?: PaymentsProviderOnboardingState;
		messages?: {
			not_supported?: string; // Message to display to the user when onboarding is not supported.
		};
		_links?: {
			onboard?: LinkData; // For gateways, this is used to start the onboarding flow.
			reset?: LinkData; // For gateways, this is used to reset the account/onboarding.
		};
		recommended_payment_methods?: RecommendedPaymentMethod[];
		type?: string;
	};
	tags?: string[];
	_incentive?: PaymentsProviderIncentive;
};

// Represents a payment gateway in the main providers list.
export type PaymentGatewayProvider = PaymentsProvider & {
	_order: number;
	supports: string[];
	management: ManagementData;
	state: PaymentsProviderState;
	onboarding: {
		state: PaymentsProviderOnboardingState;
		messages: {
			not_supported?: string; // Message to display to the user when onboarding is not supported.
		};
		_links: {
			onboard: LinkData;
			reset: LinkData;
			disable_test_account?: LinkData; // URL to disable the test account before proceeding to live account setup.
		};
		recommended_payment_methods: RecommendedPaymentMethod[];
		type: string;
	};
};

// Represents an offline payment method provider in the main providers list.
export type OfflinePaymentMethodProvider = PaymentsProvider & {
	_order: number;
	supports: string[];
	management: ManagementData;
	state: PaymentsProviderState;
	onboarding: {
		state: PaymentsProviderOnboardingState;
		_links: {
			onboard: LinkData;
		};
	};
};

// Represents an offline payment methods group provider in the main providers list.
export type OfflinePmsGroupProvider = PaymentsProvider & {
	_order: number;
	management: ManagementData;
};

// Represents a payments extension suggestion provider in the main providers list.
export type PaymentsExtensionSuggestionProvider = PaymentsProvider & {
	_order: number;
	onboarding: {
		state: PaymentsProviderOnboardingState;
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

// Represents a suggested payments extension outside the main providers list.
export type SuggestedPaymentsExtension = PaymentsEntity & {
	_type: string;
	_priority: number;
	category: string;
	image: string;
	short_description: string;
	tags: string[];
	links: PaymentsProviderLink[];
	_incentive?: PaymentsProviderIncentive;
};

export type SuggestedPaymentsExtensionCategory = {
	id: string;
	_priority: number;
	title: string;
	description: string;
};

export type PaymentsSettingsState = {
	providers: PaymentsProvider[];
	offlinePaymentGateways: OfflinePaymentMethodProvider[];
	suggestions: SuggestedPaymentsExtension[];
	suggestionCategories: SuggestedPaymentsExtensionCategory[];
	isFetching: boolean;
	errors: Record< string, unknown >;
	isWooPayEligible: boolean;
};

export type OrderMap = Record< string, number >;

export type PaymentProvidersResponse = {
	providers: PaymentsProvider[];
	offline_payment_methods: OfflinePaymentMethodProvider[];
	suggestions: SuggestedPaymentsExtension[];
	suggestion_categories: SuggestedPaymentsExtensionCategory[];
};

export type EnableGatewayResponse = {
	success: boolean;
	data: unknown;
};

export interface WooPayEligibilityResponse {
	is_eligible: boolean;
}
