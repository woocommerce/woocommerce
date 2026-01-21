#!/usr/bin/env node
/* eslint-disable no-console */
const fs = require( 'fs' );
const path = require( 'path' );
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

const url = 'https://codevitals.run/api/log?token=' + token;

fetch( url, {
	method: 'POST',
	headers: {
		'Content-Type': 'application/json',
	},
	body: data,
} )
	.then( async ( res ) => {
		console.log( `url: ${ url.replace( /token=.*/, 'token=***' ) }` );
		console.log( `statusCode: ${ res.status }` );
		console.log( `statusMessage: ${ res.statusText }` );

		const body = await res.text();
		if ( body ) {
			console.log( body );
		}
	} )
	.catch( ( error ) => {
		console.error( error );
	} );
