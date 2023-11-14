/**
 * External dependencies
 */
const { readFileSync, existsSync } = require( 'fs' );
const chalk = require( 'chalk' );
const { PERFORMANCE_REPORT_FILENAME } = require( '../../utils/constants' );

class PerformanceReporter {
	onRunComplete() {
		if ( ! existsSync( PERFORMANCE_REPORT_FILENAME ) ) {
			return;
		}
		const reportFileContents = readFileSync( PERFORMANCE_REPORT_FILENAME )
			.toString()
			.split( '\n' )
			.slice( 0, -1 )
			.map( ( line ) => JSON.parse( line ) );

		reportFileContents.forEach( ( testReport ) => {
			// eslint-disable-next-line no-console
			console.log(
				chalk.black.bgGreen.underline.bold( testReport.description )
			);
			// eslint-disable-next-line no-console
			console.log( chalk.red( `Longest: ${ testReport.longest }ms` ) );
			// eslint-disable-next-line no-console
			console.log(
				chalk.green( `Shortest: ${ testReport.shortest }ms` )
			);
			// eslint-disable-next-line no-console
			console.log(
				chalk.yellow( `Average: ${ testReport.average.toFixed() }ms` )
			);
			// eslint-disable-next-line no-console
			console.log( '' );
		} );
	}
}

module.exports = PerformanceReporter;
