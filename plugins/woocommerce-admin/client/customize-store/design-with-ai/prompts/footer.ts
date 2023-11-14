/**
 * External dependencies
 */
import { z } from 'zod';

const footerChoices = [
	{
		slug: 'woocommerce-blocks/footer-simple-menu',
		label: 'Footer with Simple Menu',
	},
	{
		slug: 'woocommerce-blocks/footer-with-3-menus',
		label: 'Footer with 3 Menus',
	},
	{
		slug: 'woocommerce-blocks/footer-large',
		label: 'Large Footer',
	},
];

const allowedFooter: string[] = footerChoices.map( ( footer ) => footer.slug );

export const footerValidator = z.object( {
	slug: z.string().refine( ( slug ) => allowedFooter.includes( slug ), {
		message: 'Footer not part of allowed list',
	} ),
} );

export const defaultFooter = {
	queryId: 'default_footer',

	// make sure version is updated every time the prompt is changed
	version: '2023-09-19',
	prompt: ( businessDescription: string, look: string, tone: string ) => {
		return `
            You are a WordPress theme expert. Analyse the following store description, merchant's chosen look and tone, and determine the most appropriate footer.
            Respond only with one footer and only its JSON.

            Chosen look and tone: ${ look } look, ${ tone } tone.
            Business description: ${ businessDescription }

            Footer to choose from: 
            ${ JSON.stringify( footerChoices ) }
        `;
	},
	responseValidation: footerValidator.parse,
};
