'use strict';
/* eslint no-console: 0 */

/**
 * External dependencies
 */
const chalk = require( 'chalk' );

/**
 * Internal dependencies
 */
const {
	params,
	exceptions,
	returns,
	example,
	related,
	files,
} = require( '../format-hook-doc' );
const {
	createDocs,
	generateHookName,
	generateIntroduction,
	sectionWithHeading,
	contentWithHeading,
	generateToc,
} = require( '../utilities' );

const generate = ( hooks ) => {
	console.log( chalk.blue( 'Generating Filter Docs...' ) );

	const jsonDocs = [
		{ html: '<!-- DO NOT UPDATE THIS DOC DIRECTLY -->' },
		{
			html:
				'<!-- Use `npm run build:docs` to automatically build hook documentation -->',
		},
		{ h1: 'Filters' },
		{ h2: 'Table of Contents' },
		...generateToc( hooks ),
		{ hr: '' },
		...hooks.map( ( hook ) => {
			const hookDocs = hook.doc || [];

			return [
				...generateHookName( hook ),
				...generateIntroduction( hook ),
				...contentWithHeading(
					hook.doc.long_description_html,
					'Description'
				),
				...sectionWithHeading( params( hookDocs ), 'Parameters' ),
				...sectionWithHeading( exceptions( hookDocs ), 'Exceptions' ),
				...sectionWithHeading( returns( hookDocs ), 'Returns' ),
				...sectionWithHeading( example( hookDocs ), 'Example' ),
				...sectionWithHeading( related( hookDocs ), 'See' ),
				...sectionWithHeading( files( hook.file ), 'Source' ),
				{ hr: '' },
			].filter( Boolean );
		} ),
	];
	createDocs( 'docs/extensibility/filters.md', jsonDocs );
	console.log( chalk.green( 'Done!' ) );
};

module.exports = { generate };
