/**
 * External dependencies
 */
import { z } from 'zod';

const headerChoices = [
	{
		slug: 'woocommerce-blocks/header-essential',
		label: 'Essential Header',
	},
	{
		slug: 'woocommerce-blocks/header-centered-menu',
		label: 'Centered Menu with search Header',
	},
	{
		slug: 'woocommerce-blocks/header-minimal',
		label: 'Minimal Header',
	},
	{
		slug: 'woocommerce-blocks/header-large',
		label: 'Large Header',
	},
];

const allowedHeaders: string[] = headerChoices.map( ( header ) => header.slug );

export const headerValidator = z.object( {
	slug: z.string().refine( ( slug ) => allowedHeaders.includes( slug ), {
		message: 'Header not part of allowed list',
	} ),
} );

export const defaultHeader = {
	queryId: 'default_header',

	// make sure version is updated every time the prompt is changed
	version: '2023-09-19',
	prompt: ( businessDescription: string, look: string, tone: string ) => {
		return `
            You are a WordPress theme expert. Analyse the following store description, merchant's chosen look and tone, and determine the most appropriate header.
            Respond only with one header and only its JSON.

            Chosen look and tone: ${ look } look, ${ tone } tone.
            Business description: ${ businessDescription }

            Headers to choose from:
            ${ JSON.stringify( headerChoices ) }
        `;
	},
	responseValidation: headerValidator.parse,
};
