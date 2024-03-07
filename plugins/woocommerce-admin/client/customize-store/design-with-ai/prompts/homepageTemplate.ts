/**
 * External dependencies
 */
import { z } from 'zod';

/**
 * Internal dependencies
 */
import { HOMEPAGE_TEMPLATES } from '~/customize-store/data/homepageTemplates';

const allowedTemplates: string[] = Object.keys( HOMEPAGE_TEMPLATES );

export const homepageTemplateValidator = z.object( {
	homepage_template: z
		.string()
		.refine( ( template ) => allowedTemplates.includes( template ), {
			message: 'Template not part of allowed list',
		} ),
} );

export const defaultHomepageTemplate = {
	queryId: 'default_template',

	// make sure version is updated every time the prompt is changed
	version: '2023-09-21',
	prompt: ( businessDescription: string, look: string, tone: string ) => {
		return `
            You are a WordPress theme expert and a business analyst. Analyse the following store description, merchant's chosen look and tone, template metadata, and determine the most appropriate template.
            Consider the business size based on business description, where some templates are more suited for small and medium businesses, and some for large businesses.
						Use metadata with the key "businessType" to determine the business size and type.
            This is important, respond only with ONE template identifier (e.g., { "homepage_template": "template1" }). Do not explain or add any other information since it will fail the validation.

            Chosen look and tone: ${ look } look, ${ tone } tone.
            Business description: ${ businessDescription }

            Templates to choose from:
            ${ JSON.stringify( HOMEPAGE_TEMPLATES ) }
        `;
	},
	responseValidation: homepageTemplateValidator.parse,
};
