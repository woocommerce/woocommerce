#!/usr/bin/env node
/* eslint-disable no-console */
const fs = require( 'fs' );
const path = require( 'path' );
const https = require( 'https' );
const [ token, branch, hash, baseHash, timestamp ] = process.argv.slice( 2 );

const resultsFiles = [
	{
		file: 'editor.performance-results.json',
		metricsPrefix: 'editor-',
	},
	{
		file: 'product-editor.performance-results.json',
		metricsPrefix: 'product-editor-',
	},
	{
		file: 'frontend.performance-results.json',
		metricsPrefix: 'frontend-',
	},
];
const ARTIFACTS_PATH =
	process.env.WP_ARTIFACTS_PATH || path.join( process.cwd(), 'artifacts' );

const performanceResults = resultsFiles.map( ( { file } ) =>
	JSON.parse( fs.readFileSync( path.join( ARTIFACTS_PATH, file ), 'utf8' ) )
);

const data = JSON.stringify( {
	branch,
	hash,
	baseHash,
	timestamp,
	metrics: resultsFiles.reduce( ( result, { metricsPrefix }, index ) => {
		return {
			...result,
			...Object.fromEntries(
				Object.entries( performanceResults[ index ][ hash ] ?? {} ).map(
					( [ key, value ] ) => [
						metricsPrefix + key,
						typeof value === 'object'
							? value.q50
							: Number( value || 0.00001 ).toFixed( 5 ),
					]
				)
			),
		};
	}, {} ),
	baseMetrics: resultsFiles.reduce( ( result, { metricsPrefix }, index ) => {
		return {
			...result,
			...Object.fromEntries(
				Object.entries(
					performanceResults[ index ][ baseHash ] ?? {}
				).map( ( [ key, value ] ) => [
					metricsPrefix + key,
					typeof value === 'object'
						? value.q50
						: Number( value || 0.00001 ).toFixed( 5 ),
				] )
			),
		};
	}, {} ),
} );

const options = {
	hostname: 'codevitals.run',
	port: 443,
	path: '/api/log?token=' + token,
	method: 'POST',
	headers: {
		'Content-Type': 'application/json',
		'Content-Length': data.length,
	},
};

const req = https.request( options, ( res ) => {
	console.log( `hostname: ${ options.hostname }` );
	console.log( `statusCode: ${ res.statusCode }` );
	console.log( `statusMessage: ${ res.statusMessage }` );

	res.on( 'data', ( d ) => {
		process.stdout.write( d );
	} );
} );

req.on( 'error', ( error ) => {
	console.error( error );
} );

req.write( data );
req.end();
