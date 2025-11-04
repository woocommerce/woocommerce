/**
 * External dependencies
 */
import type { TrustedTypePolicy } from 'trusted-types';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from './sanitize';

/**
 * The type for our trusted types policy.
 */
export type WooCommerceSanitizePolicyType = Pick<
	TrustedTypePolicy,
	'name' | 'createHTML'
>;

/**
 * Cached policy instance to ensure it's only created once.
 */
let policyInstance: WooCommerceSanitizePolicyType | null | undefined;

/**
 * Get or create a trusted types policy for DOMPurify.
 *
 * @return TrustedTypePolicy object or null if not supported.
 */
export function getTrustedTypesPolicy(): WooCommerceSanitizePolicyType | null {
	if ( policyInstance !== undefined ) {
		return policyInstance;
	}

	if ( typeof window === 'undefined' || ! window.trustedTypes ) {
		policyInstance = null;
		return null;
	}

	try {
		policyInstance = window.trustedTypes.createPolicy(
			'woocommerce-sanitize',
			{
				createHTML: ( input: string ): string => sanitizeHTML( input ),
			}
		);
	} catch ( error ) {
		policyInstance = null;
		// eslint-disable-next-line no-console
		console.warn(
			'Failed to create "woocommerce-sanitize" trusted type policy:',
			error
		);
	}

	return policyInstance;
}
