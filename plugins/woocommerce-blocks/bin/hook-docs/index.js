#!/usr/bin/env node
'use strict';
/* eslint no-console: 0 */
const chalk = require( 'chalk' );

try {
	const { generate: generateActionDocs } = require( './actions' );
	const { generate: generateFilterDocs } = require( './filters' );

	console.log( chalk.blue( "Let's create some docs!" ) );

	const actions = require( './data/actions.json' ).hooks;
	const filters = require( './data/filters.json' ).hooks;

	generateActionDocs( actions );
	generateFilterDocs( filters );
} catch ( error ) {
	console.log( chalk.red( error.message ) );
}
