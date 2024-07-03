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
	type: WPTemplateType;
}

export interface TemplateCompiler {
	compile: ( data?: unknown ) => Promise< WPTemplate >;
}

/**
 * Retrieves all available templates.
 */
export async function getTemplates( this: RequestUtils ) {
	const templates = await this.rest< WPTemplate[] >( {
		method: 'GET',
		path: '/wp/v2/templates',
	} );

	return templates;
}

/**
 * Reverts a template to its original state.
 */
export async function revertTemplate( this: RequestUtils, slug: string ) {
	const restPath = `/wp/v2/templates/${ slug }`;

	const template = await this.rest( {
		method: 'GET',
		path: restPath,
	} );

	await this.rest( {
		method: 'POST',
		path: restPath,
		data: {
			id: slug,
			content: template.content.raw,
			source: 'theme',
		},
	} );
}

/**
 * Creates a WP template from a Handlebars template file located in the
 * tests/e2e/content-templates directory.
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
		'../../content-templates',
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

	return <TemplateCompiler>{
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
