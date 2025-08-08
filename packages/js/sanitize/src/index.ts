/**
 * External dependencies
 */
import { sanitize, setConfig } from 'dompurify';

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

// Define TrustedTypePolicy interface
interface TrustedTypePolicy {
	createHTML: ( string: string ) => string;
	createScript: ( string: string ) => string;
	createScriptURL: ( string: string ) => string;
}

/**
 * Default allowed HTML tags for basic sanitization.
 */
export const DEFAULT_ALLOWED_TAGS = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'abbr',
] as const;

/**
 * Default allowed HTML attributes for basic sanitization.
 */
export const DEFAULT_ALLOWED_ATTR = [
	'target',
	'href',
	'rel',
	'name',
	'download',
	'title',
] as const;

/**
 * Creates a trusted type policy for DOMPurify to avoid conflicts with DOMPurify's default policy name.
 * This policy is specifically for WooCommerce's use of DOMPurify.
 */
function createWooCommerceTrustedTypesPolicy(): void {
	// Only create the policy if trusted types are supported and we haven't already created it
	if (
		typeof window !== 'undefined' &&
		window.trustedTypes &&
		! window.trustedTypes.defaultPolicy
	) {
		try {
			// Create a WooCommerce-specific policy name to avoid conflicts
			const policy = window.trustedTypes.createPolicy(
				'woocommerce-dompurify',
				{
					createHTML: ( string: string ) => string,
					createScript: ( string: string ) => string,
					createScriptURL: ( string: string ) => string,
				}
			);

			// Set this as the default policy for DOMPurify
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			( setConfig as any )( {
				TRUSTED_TYPES_POLICY: policy,
			} );
		} catch ( error ) {
			// If policy creation fails, log a warning but don't break functionality
			// eslint-disable-next-line no-console
			console.warn(
				'Failed to create WooCommerce trusted types policy:',
				error
			);
		}
	}
}

/**
 * Initialize the trusted types policy for DOMPurify.
 * This should be called early in the application lifecycle.
 */
export function initializeTrustedTypesPolicy(): void {
	// Create the policy immediately if possible
	createWooCommerceTrustedTypesPolicy();

	// Also try to create it when the DOM is ready
	if ( typeof document !== 'undefined' ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener(
				'DOMContentLoaded',
				createWooCommerceTrustedTypesPolicy
			);
		} else {
			createWooCommerceTrustedTypesPolicy();
		}
	}
}

/**
 * Configuration options for HTML sanitization.
 */
export interface SanitizeConfig {
	/** Allowed HTML tags */
	tags?: readonly string[];
	/** Allowed HTML attributes */
	attr?: readonly string[];
}

/**
 * Sanitizes HTML content using DOMPurify with default allowed tags and attributes.
 *
 * @param html - The HTML content to sanitize.
 * @param config - Optional configuration for allowed tags and attributes.
 * @return Sanitized HTML content.
 */
export function sanitizeHTML( html: string, config?: SanitizeConfig ): string {
	const allowedTags = config?.tags || DEFAULT_ALLOWED_TAGS;
	const allowedAttr = config?.attr || DEFAULT_ALLOWED_ATTR;

	return sanitize( html, {
		ALLOWED_TAGS: [ ...allowedTags ],
		ALLOWED_ATTR: [ ...allowedAttr ],
	} );
}

// Initialize trusted types policy when the module is loaded
initializeTrustedTypesPolicy();
