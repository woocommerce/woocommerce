/**
 * External dependencies
 */
import DOMPurify from 'dompurify';

// Extend Window interface to include trustedTypes
declare global {
	interface Window {
		trustedTypes?: {
			createPolicy: (
				name: string,
				rules: {
					createHTML?: ( string: string ) => string;
					createScript?: ( string: string ) => string;
					createScriptURL?: ( string: string ) => string;
				}
			) => TrustedTypePolicy;
			defaultPolicy?: TrustedTypePolicy;
		};
	}
}

/**
 * TrustedTypePolicy interface.
 */
interface TrustedTypePolicy {
	createHTML: ( string: string ) => string;
	createScript: ( string: string ) => string;
	createScriptURL: ( string: string ) => string;
}

/**
 * The name of the trusted types policy.
 */
export const TRUSTED_POLICY_NAME = 'woocommerce-sanitize';

/**
 * Create a trusted types policy for DOMPurify.
 *
 * @return TrustedTypePolicy object.
 */
function createPolicy(): TrustedTypePolicy | null {
	if ( ! window || ! window.trustedTypes ) {
		return null;
	}

	const policy = window.trustedTypes.createPolicy( TRUSTED_POLICY_NAME, {
		createHTML: ( string: string ) => string,
		createScriptURL: ( url ) => url,
	} );

	return policy;
}

/**
 * Initialize the trusted types policy for DOMPurify.
 * This should be called early in the application lifecycle.
 */
export function initializeTrustedTypesPolicy(): void {
	const policy = createPolicy();

	if ( ! policy ) {
		return;
	}

	// Set this as the policy for DOMPurify
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	( DOMPurify.setConfig as any )( {
		TRUSTED_TYPES_POLICY: policy,
	} );
}
