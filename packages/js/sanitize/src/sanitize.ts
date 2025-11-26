/**
 * External dependencies
 */
import DOMPurify from 'dompurify';

/**
 * Internal dependencies
 */
import { getNoopTrustedTypesPolicy } from './noop-trusted-types-policy';

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
 * The set of supported return type kinds for sanitized content.
 * These are the configuration values you can pass via `returnType`.
 */
export type SanitizeReturnKind =
	| 'string'
	| 'HTMLBodyElement'
	| 'DocumentFragment';

/**
 * Mapping between `SanitizeReturnKind` and the actual returned value types.
 */
type ReturnTypeMap = {
	string: string;
	HTMLBodyElement: HTMLBodyElement;
	DocumentFragment: DocumentFragment;
};

/**
 * Union of the concrete value types this sanitizer can return.
 * Useful when you want to accept any possible sanitizer output.
 */
export type SanitizeReturnType = ReturnTypeMap[ keyof ReturnTypeMap ];

/**
 * Configuration options for HTML sanitization.
 */
export interface SanitizeConfig< T extends SanitizeReturnKind = 'string' > {
	/** Allowed HTML tags */
	tags?: readonly string[];
	/** Allowed HTML attributes */
	attr?: readonly string[];
	/** Desired return type for the sanitized content. Defaults to 'string'. */
	returnType?: T;
}

export function sanitizeHTML(
	html: string | null | undefined,
	config?: SanitizeConfig< 'string' >
): string;
export function sanitizeHTML(
	html: string | null | undefined,
	config: SanitizeConfig< 'HTMLBodyElement' >
): HTMLBodyElement;
export function sanitizeHTML(
	html: string | null | undefined,
	config: SanitizeConfig< 'DocumentFragment' >
): DocumentFragment;
export function sanitizeHTML< T extends SanitizeReturnKind = 'string' >(
	html: string | null | undefined,
	config?: SanitizeConfig< T >
): ReturnTypeMap[ T ];

/**
 * Sanitizes HTML content using DOMPurify with default allowed tags and attributes.
 *
 * @param html   - The HTML content to sanitize.
 * @param config - Optional configuration for allowed tags and attributes.
 *
 * @return Sanitized HTML content.
 */
export function sanitizeHTML< T extends SanitizeReturnKind = 'string' >(
	html: string | null | undefined,
	config?: SanitizeConfig< T >
): ReturnTypeMap[ T ] {
	const allowedTags = config?.tags || DEFAULT_ALLOWED_TAGS;
	const allowedAttr = config?.attr || DEFAULT_ALLOWED_ATTR;

	const purifyConfig: DOMPurify.Config = {
		ALLOWED_TAGS: [ ...allowedTags ],
		ALLOWED_ATTR: [ ...allowedAttr ],
	};

	// Provide a no-op TT policy (when supported) to prevent DOMPurify from
	// creating its internal policy and emitting warnings with multiple instances.
	const ttNoopPolicy = getNoopTrustedTypesPolicy();
	if ( ttNoopPolicy ) {
		purifyConfig.TRUSTED_TYPES_POLICY = ttNoopPolicy as TrustedTypePolicy;
	}

	// Only pass a single RETURN_* flag if a non-string return type is requested
	if ( config?.returnType === 'HTMLBodyElement' ) {
		purifyConfig.RETURN_DOM = true;
	} else if ( config?.returnType === 'DocumentFragment' ) {
		purifyConfig.RETURN_DOM_FRAGMENT = true;
	}

	return DOMPurify.sanitize(
		html ?? '',
		purifyConfig
	) as unknown as ReturnTypeMap[ T ];
}
