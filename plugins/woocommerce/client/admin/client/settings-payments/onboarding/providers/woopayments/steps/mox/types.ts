/**
 * Internal dependencies
 */

export type OnboardingSteps = 'business' | 'store' | 'embedded' | 'loading';

export type OnboardingFields = {
	country?: string;
	business_type?: string;
	'company.structure'?: string;
	mcc?: string;
	annual_revenue?: string;
	go_live_timeframe?: string;
};

export interface PoEligibleResponse {
	result: 'eligible' | 'not_eligible';
}

export interface PoEligibleData {
	business: {
		country: string;
		type: string;
		mcc: string;
	};
	store: {
		annual_revenue: string;
		go_live_timeframe: string;
	};
}

export interface Country {
	key: string;
	name: string;
	types: BusinessType[];
}

export interface BusinessType {
	key: string;
	name: string;
	description: string;
	structures: BusinessStructure[];
}

export interface BusinessStructure {
	key: string;
	name: string;
}

export interface MccsDisplayTreeItem {
	id: string;
	type: string;
	title: string;
	items?: MccsDisplayTreeItem[];
	mcc?: number;
	keywords?: string[];
}

export interface AccountKycSession {
	clientSecret: string;
	expiresAt: number;
	accountId: string;
	isLive: boolean;
	accountCreated: boolean;
	publishableKey: string;
	locale: string;
}

export interface FinalizeOnboardingResponse {
	success: boolean;
	params: Record< string, string >;
}
