/**
 * External dependencies
 */
import type DOMPurify from 'dompurify';

/**
 * Extract the TrustedTypesPolicy type from DOMPurify's Config.
 * This ensures our policy type matches exactly what DOMPurify expects.
 */
export type TrustedTypesPolicy = NonNullable<
	DOMPurify.Config[ 'TRUSTED_TYPES_POLICY' ]
>;

// Extend Window interface to include trustedTypes
declare global {
	interface Window {
		trustedTypes?: {
			createPolicy: (
				name: string,
				rules: {
					createHTML?: ( input: string ) => string;
					createScript?: ( input: string ) => string;
					createScriptURL?: ( input: string ) => string;
				}
			) => TrustedTypesPolicy;
			defaultPolicy?: TrustedTypesPolicy;
		};
	}
}

/**
 * The name of the trusted types policy.
 */
export const TRUSTED_POLICY_NAME = 'woocommerce-sanitize';

/**
 * Cached policy instance to ensure it's only created once.
 */
let policyInstance: TrustedTypesPolicy | null | undefined;

/**
 * Get or create a trusted types policy for DOMPurify.
 *
 * @return TrustedTypesPolicy object or null if not supported.
 */
export function getTrustedTypesPolicy(): TrustedTypesPolicy | null {
	if ( policyInstance !== undefined ) {
		return policyInstance;
	}

	if ( ! window || ! window.trustedTypes ) {
		policyInstance = null;
		return null;
	}

	policyInstance = window.trustedTypes.createPolicy( TRUSTED_POLICY_NAME, {
		createHTML: ( string: string ) => string,
		createScriptURL: ( url ) => url,
	} );

	return policyInstance;
}
