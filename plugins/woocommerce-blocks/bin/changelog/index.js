#!/usr/bin/env node
'use strict';
/* eslint no-console: 0 */
const chalk = require( 'chalk' );

try {
	const { makeChangeLog: githubMake } = require( './github' );
	const { makeChangeLog: zenhubMake } = require( './zenhub' );
	const { pkg, changelogSrcTypes } = require( './config' );

	const makeChangeLog =
		pkg.changelog.changelogSrcType === changelogSrcTypes.ZENHUB
			? zenhubMake
			: githubMake;

	makeChangeLog();
} catch ( error ) {
	console.log( chalk.red( error.message ) );
}
