/**
 * This script generates a PHP file from block JSON files.
 * To run it ( this script only ):
 *   pnpm run watch:build:project:blocks-json-to-php
 * Or, it will be run automatically when you run:
 *   pnpm run watch:build
 *
 * In watch mode:
 *   pnpm run watch:build:project:blocks-json-to-php
 * Or
 *   pnpm run watch:build
 */

const fs = require( 'fs' );
const path = require( 'path' );
const glob = require( 'glob' );
const json2php = require( 'json2php' );

const blocksDir = path.join( __dirname, '../assets/js/blocks' );
const outputFile = path.join( __dirname, '../build/blocks-json.php' );

const blocks = {};

const propertyMappings = {
	apiVersion: 'api_version',
	name: 'name',
	title: 'title',
	category: 'category',
	parent: 'parent',
	ancestor: 'ancestor',
	icon: 'icon',
	description: 'description',
	keywords: 'keywords',
	attributes: 'attributes',
	providesContext: 'provides_context',
	usesContext: 'uses_context',
	selectors: 'selectors',
	supports: 'supports',
	styles: 'styles',
	variations: 'variations',
	example: 'example',
	allowedBlocks: 'allowed_blocks',
};

glob.sync( `${ blocksDir }/**/block.json` ).forEach( ( file ) => {
	if ( file.includes( 'inner-blocks' ) ) {
		const blockJson = JSON.parse( fs.readFileSync( file, 'utf8' ) );
		const transformedBlock = {};

		Object.entries( propertyMappings ).forEach( ( [ compiledKey, expectedKey ] ) => {
			if ( blockJson.hasOwnProperty( compiledKey ) ) {
				transformedBlock[ expectedKey ] = blockJson[ compiledKey ];
			}
		} );

		blocks[ blockJson.name ] = transformedBlock;
	}
} );

const phpContent = `<?php
// This file is generated. Do not modify it manually.
return ${ json2php( blocks ) };
`;

fs.writeFileSync( outputFile, phpContent );

console.log( 'blocks-json.php has been generated with transformed properties.' );
