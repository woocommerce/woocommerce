/**
 * External dependencies
 */
import type { Block } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { TEMPLATES } from './constants';
import { TemplateDetails } from './types';

// Finds the most appropriate template details object for specific template keys such as single-product-hoodie.
export function getTemplateDetailsBySlug(
	parsedTemplate: string,
	templates: TemplateDetails
) {
	const templateKeys = Object.keys( templates );
	let templateDetails = null;

	for ( let i = 0; templateKeys.length > i; i++ ) {
		const keyToMatch = parsedTemplate.substr( 0, templateKeys[ i ].length );
		const maybeTemplate = templates[ keyToMatch ];
		if ( maybeTemplate ) {
			templateDetails = maybeTemplate;
			break;
		}
	}

	return templateDetails;
}

export function isClassicTemplateBlockRegisteredWithAnotherTitle(
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	block: Block< any > | undefined,
	parsedTemplate: string
) {
	const templateDetails = getTemplateDetailsBySlug(
		parsedTemplate,
		TEMPLATES
	);
	return block?.title !== templateDetails?.title;
}

export function hasTemplateSupportForClassicTemplateBlock(
	parsedTemplate: string,
	templates: TemplateDetails
): boolean {
	return getTemplateDetailsBySlug( parsedTemplate, templates ) ? true : false;
}
