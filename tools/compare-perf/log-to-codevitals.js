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

fetch( 'https://codevitals.run/api/log?token=' + token, {
	method: 'POST',
	headers: {
		'Content-Type': 'application/json',
	},
	body: data,
} )
	.then( async ( response ) => {
		console.log( `statusCode: ${ response.status }` );
		const text = await response.text();
		if ( text ) {
			console.log( text );
		}
	} )
	.catch( ( error ) => {
		console.error( error );
	} );
