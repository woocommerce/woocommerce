/**
 * External dependencies
 */
import type { TrustedTypePolicy } from 'trusted-types';

/**
 * The type for our no-op trusted types policy.
 */
export type WooCommerceSanitizeNoopPolicyType = Pick<
	TrustedTypePolicy,
	'name' | 'createHTML'
>;

/**
 * Cached no-op policy instance to avoid duplicate creation.
 */
let noopPolicyInstance: WooCommerceSanitizeNoopPolicyType | null | undefined;

export function getNoopTrustedTypesPolicy(): WooCommerceSanitizeNoopPolicyType | null {
	if ( noopPolicyInstance !== undefined ) {
		return noopPolicyInstance;
	}

	if ( typeof window === 'undefined' || ! window.trustedTypes ) {
		noopPolicyInstance = null;
		return null;
	}

	try {
		noopPolicyInstance = window.trustedTypes.createPolicy(
			'woocommerce-sanitize-noop',
			{
				createHTML: ( input: string ): string => input,
			}
		);
	} catch ( error ) {
		noopPolicyInstance = null;
		// eslint-disable-next-line no-console
		console.warn(
			'Failed to create "woocommerce-sanitize-noop" trusted type policy:',
			error
		);
	}

	return noopPolicyInstance;
}
