#!/usr/bin/env node
'use strict';
/* eslint no-console: 0 */
const chalk = require( 'chalk' );

const groupByHook = ( hooks, hook ) => {
	if ( hooks[ hook.name ] !== undefined ) {
		if ( hooks[ hook.name ].file !== hook.file ) {
			hooks[ hook.name ].file.push( hook.file );
		}
		return hooks; // skip or return updated record.
	}

	hooks[ hook.name ] = {
		...hook,
		file: [ hook.file ], // Use array of files to support hooks used across the codebase.
	};

	return hooks;
};

try {
	const { generate: generateActionDocs } = require( './actions' );
	const { generate: generateFilterDocs } = require( './filters' );

	console.log( chalk.blue( "Let's create some docs!" ) );

	const rawActions = require( './data/actions.json' ).hooks;
	const rawFilters = require( './data/filters.json' ).hooks;

	// Skip duplicates.
	const actions = rawActions.reduce( groupByHook, {} );
	const filters = rawFilters.reduce( groupByHook, {} );

	generateActionDocs( Object.values( actions ) );
	generateFilterDocs( Object.values( filters ) );
} catch ( error ) {
	console.log( chalk.red( error.message ) );
}
