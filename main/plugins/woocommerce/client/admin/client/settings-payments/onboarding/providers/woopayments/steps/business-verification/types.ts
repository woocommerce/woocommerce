/**
 * Internal dependencies
 */

export type OnboardingSteps =
	| 'activate'
	| 'business'
	| 'store'
	| 'embedded'
	| 'loading';

export type OnboardingFields = {
	country?: string;
	business_type?: string;
	'company.structure'?: string;
	mcc?: string;
};

export interface Country {
	key: string;
	name: string;
	types: BusinessType[];
}

export interface BusinessType {
	key: string;
	name: string;
	description: string;
	requires_structure?: boolean;
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

/**
 * Embedded KYC session.
 */
export interface EmbeddedKycSession {
	clientSecret: string;
	expiresAt: number;
	accountId: string;
	isLive: boolean;
	accountCreated: boolean;
	publishableKey: string;
	locale: string;
}

/**
 * Embedded KYC session result.
 */
export interface EmbeddedKycSessionCreateResult {
	session: EmbeddedKycSession;
}

export type EmbeddedAccountInitializationFailureReason =
	| 'bad_session'
	| 'init_error';

export interface EmbeddedAccountInitializationFailure {
	reason: EmbeddedAccountInitializationFailureReason;
	message: string;
	receivedKeys?: string[];
}

/**
 * Finalize embedded KYC session response.
 */
export interface FinalizeEmbeddedKycSessionResponse {
	success: boolean;
	params: Record< string, string >;
}
