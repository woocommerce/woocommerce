/**
 * External dependencies
 */
import type { TrustedTypePolicy } from 'trusted-types';

/**
 * Cached no-op policy instance to avoid duplicate creation.
 */
let noopPolicyInstance: TrustedTypePolicy | null | undefined;

export function getNoopTrustedTypesPolicy(): TrustedTypePolicy | null {
	if ( noopPolicyInstance !== undefined ) {
		return noopPolicyInstance;
	}

	if ( typeof window === 'undefined' || ! window.trustedTypes ) {
		noopPolicyInstance = null;
		return null;
	}

	noopPolicyInstance = window.trustedTypes.createPolicy(
		'woocommerce-sanitize-noop',
		{
			createHTML: ( input: string ): string => input,
			createScriptURL: ( input: string ): string => input,
		}
	) as TrustedTypePolicy;

	return noopPolicyInstance;
}
