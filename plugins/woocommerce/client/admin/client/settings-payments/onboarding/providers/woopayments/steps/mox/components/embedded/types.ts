/**
 * Internal dependencies
 */

/**
 * Account session.
 */
export interface AccountSession {
	clientSecret: string;
	expiresAt: number;
	accountId: string;
	isLive: boolean;
	accountCreated: boolean;
	publishableKey: string;
	locale: string;
}
