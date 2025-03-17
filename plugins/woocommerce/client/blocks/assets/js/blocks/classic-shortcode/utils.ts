/**
 * Internal dependencies
 */
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
