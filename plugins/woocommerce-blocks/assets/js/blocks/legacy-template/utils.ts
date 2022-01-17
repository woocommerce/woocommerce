/**
 * Internal dependencies
 */
import { TemplateAttributes } from './types';

export function beginsWith( needle: string, haystack: string ): boolean {
	return haystack.substr( 0, needle.length ) === needle;
}

export function getMatchingTemplateData(
	templates: Record< string, TemplateAttributes >,
	slug: string
): TemplateAttributes | null {
	const templateSlugs = Object.keys( templates );
	const matchingSlugs = templateSlugs.filter( ( templateSlug ) =>
		slug.startsWith( templateSlug )
	);

	if ( matchingSlugs.length === 0 ) {
		return null;
	}

	return templates[ matchingSlugs[ 0 ] ];
}
