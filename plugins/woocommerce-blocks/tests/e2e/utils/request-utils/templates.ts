/**
 * Internal dependencies
 */
import type { RequestUtils } from './index';

interface Template {
	wp_id: number;
	id: string;
	content: {
		raw: string;
	};
}

/**
 * Retrieves all available templates.
 */
export async function getTemplates( this: RequestUtils ) {
	const templates = await this.rest< Template[] >( {
		method: 'GET',
		path: '/wp/v2/templates',
	} );

	return templates;
}

/**
 * Reverts a template to its original state.
 */
export async function revertTemplate( this: RequestUtils, slug: string ) {
	const path = `/wp/v2/templates/${ slug }`;

	const template = await this.rest< Template >( {
		method: 'GET',
		path,
	} );

	await this.rest( {
		method: 'POST',
		path,
		data: {
			id: slug,
			content: template.content.raw,
			source: 'theme',
		},
	} );
}
