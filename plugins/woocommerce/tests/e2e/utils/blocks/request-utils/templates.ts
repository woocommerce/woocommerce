/**
 * External dependencies
 */
import path from 'path';
import { readFile } from 'fs/promises';
import Handlebars from 'handlebars';

/**
 * Internal dependencies
 */
import type { RequestUtils } from './index';

// We need to re-define the Template and TemplateType interfaces under a
// different names because of the conflicts caused by the core E2E
// utils already defining, but not exporting them.
// @todo: Remove this when the core E2E utils export these interfaces and we can
// use them directly.
export type WPTemplateType = 'wp_template' | 'wp_template_part';
export interface WPTemplate {
	wp_id: number;
	id: string;
	slug: string;
	type: WPTemplateType;
	origin: 'plugin' | 'theme' | null;
	content: {
		raw: string;
	};
}

export interface TemplateCompiler {
	compile: ( data?: unknown ) => Promise< WPTemplate >;
}

function getTemplateRestPath(
	templateType: WPTemplateType,
	templateId?: string
) {
	const endpoint =
		templateType === 'wp_template' ? 'templates' : 'template-parts';

	return `/wp/v2/${ endpoint }${ templateId ? `/${ templateId }` : '' }`;
}

/**
 * Retrieves all available templates.
 */
export async function getTemplates(
	this: RequestUtils,
	templateType: WPTemplateType = 'wp_template'
) {
	const templates = await this.rest< WPTemplate[] >( {
		method: 'GET',
		path: getTemplateRestPath( templateType ),
	} );

	return templates;
}

/**
 * Retrieves a template or template part.
 */
export async function getTemplate(
	this: RequestUtils,
	templateType: WPTemplateType,
	templateId: string
) {
	return await this.rest< WPTemplate >( {
		method: 'GET',
		path: getTemplateRestPath( templateType, templateId ),
	} );
}

/**
 * Updates the content of a template or template part.
 */
export async function updateTemplateContent(
	this: RequestUtils,
	templateType: WPTemplateType,
	templateId: string,
	content: string
) {
	return await this.rest< WPTemplate >( {
		method: 'POST',
		path: getTemplateRestPath( templateType, templateId ),
		data: {
			id: templateId,
			content,
		},
	} );
}

/**
 * Reverts a template to its original state.
 */
export async function revertTemplate(
	this: RequestUtils,
	templateType: WPTemplateType,
	templateId: string
) {
	const restPath = getTemplateRestPath( templateType, templateId );
	const template = await this.getTemplate( templateType, templateId );

	// User-created templates have no underlying theme or plugin template to
	// restore, so remove the custom template instead of resetting its source.
	if ( template.origin === null ) {
		await this.rest( {
			method: 'DELETE',
			path: restPath,
			params: { force: true },
		} );
		return;
	}

	await this.rest( {
		method: 'POST',
		path: restPath,
		data: {
			id: templateId,
			content: template.content.raw,
			source: 'theme',
		},
	} );
}

/**
 * Creates a WP template from a Handlebars template file located in the
 * tests/e2e/content-templates/blocks directory.
 */
export async function createTemplateFromFile(
	this: RequestUtils,
	name: string
) {
	const [ slug, title ] = name.split( '_' );
	if ( ! slug || ! title ) {
		throw new Error( '`name` must be in the format "<slug>_<title>"' );
	}

	const filePrefix = 'template';
	const filePath = path.resolve(
		__dirname,
		'../../../content-templates/blocks',
		`${ filePrefix }_${ name }.handlebars` // e.g. template_product-archive_with-custom-filters.handlebars
	);

	const fileContent = await readFile( filePath, 'utf8' );

	Handlebars.registerPartial(
		'wp-block',
		`
	<!-- wp:{{blockName}} {{{stringify attributes}}} -->
	{{> @partial-block }}
	<!-- /wp:{{blockName}} -->
	`
	);

	Handlebars.registerHelper( 'stringify', function ( context ) {
		return JSON.stringify( context );
	} );

	const compiledTemplate = Handlebars.compile( fileContent );

	return < TemplateCompiler >{
		compile: async ( data = {} ) => {
			const content = compiledTemplate( data );

			return await this.createTemplate( 'wp_template', {
				slug,
				title,
				content,
			} );
		},
	};
}
