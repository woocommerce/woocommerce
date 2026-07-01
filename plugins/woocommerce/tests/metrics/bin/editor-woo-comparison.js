#!/usr/bin/env node
/* eslint-disable no-console */
/**
 * External dependencies
 */
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );

const WOO_PLUGIN_PATH = process.cwd().endsWith( 'plugins/woocommerce' )
	? process.cwd()
	: path.resolve( process.cwd(), 'plugins/woocommerce' );
const runId = new Date().toISOString().replace( /[:.]/g, '-' );
const artifactsPath =
	process.env.WP_ARTIFACTS_PATH ||
	path.join( '/tmp/wc-editor-woo-comparison', runId );

function runCommand( command, args, options = {} ) {
	if ( options.logCommand !== false ) {
		console.log(
			options.displayCommand || [ command, ...args ].join( ' ' )
		);
	}

	try {
		return execFileSync( command, args, {
			cwd: WOO_PLUGIN_PATH,
			encoding: 'utf8',
			stdio: options.stdio || 'inherit',
			env: {
				...process.env,
				...( options.env || {} ),
			},
		} );
	} catch ( error ) {
		if ( options.stdio === 'pipe' ) {
			process.stdout.write( error.stdout || '' );
			process.stderr.write( error.stderr || '' );
		}
		throw error;
	}
}

function wpCli( args, options = {} ) {
	runCommand(
		'pnpm',
		[ 'wp-env', 'run', 'tests-cli', 'wp', ...args ],
		options
	);
}

function readResults( stateId ) {
	const resultsPath = path.join(
		artifactsPath,
		`editor_${ stateId }_round-1.performance-results.json`
	);

	return JSON.parse( fs.readFileSync( resultsPath, 'utf8' ) );
}

function runEditorMetrics( stateId ) {
	runCommand(
		'pnpm',
		[
			'playwright',
			'test',
			'--config=tests/metrics/playwright.config.js',
			'editor',
		],
		{
			env: {
				USE_WP_ENV: '1',
				WP_ARTIFACTS_PATH: artifactsPath,
				RESULTS_ID: `editor_${ stateId }_round-1`,
			},
		}
	);

	return readResults( stateId );
}

function buildComparison( baseResults, targetResults, baseLabel, targetLabel ) {
	const comparison = {};
	const metrics = new Set( [
		...Object.keys( baseResults ),
		...Object.keys( targetResults ),
	] );

	for ( const metric of metrics ) {
		const base = baseResults[ metric ];
		const target = targetResults[ metric ];
		const delta = target - base;
		const deltaPercent = base ? ( delta / base ) * 100 : null;

		comparison[ metric ] = {
			[ baseLabel ]: base,
			[ targetLabel ]: target,
			delta,
			deltaPercent,
		};
	}

	return comparison;
}

function printComparison( title, comparison, baseLabel, targetLabel ) {
	const printable = {};

	for ( const [ metric, values ] of Object.entries( comparison ) ) {
		printable[ metric ] = {
			[ baseLabel ]: values[ baseLabel ],
			[ targetLabel ]: values[ targetLabel ],
			delta: values.delta,
			deltaPercent:
				values.deltaPercent === null
					? 'n/a'
					: values.deltaPercent.toFixed( 2 ) + '%',
		};
	}

	console.log( `\n${ title }\n` );
	console.table( printable );
}

function main() {
	fs.mkdirSync( artifactsPath, { recursive: true } );
	console.log( `Writing artifacts to ${ artifactsPath }` );

	let inactiveResults;
	let activeResults;

	try {
		wpCli( [ 'plugin', 'deactivate', 'woocommerce' ] );
		inactiveResults = runEditorMetrics( 'woo-inactive' );

		wpCli( [ 'plugin', 'activate', 'woocommerce' ] );
		activeResults = runEditorMetrics( 'woo-active' );
	} finally {
		wpCli( [ 'plugin', 'activate', 'woocommerce' ] );
	}

	const activeComparison = buildComparison(
		inactiveResults,
		activeResults,
		'inactive',
		'active'
	);
	const comparisonPath = path.join(
		artifactsPath,
		'editor-woo-comparison.json'
	);
	const resultsPath = path.join( artifactsPath, 'editor-woo-results.json' );

	fs.writeFileSync(
		comparisonPath,
		JSON.stringify( activeComparison, null, 2 )
	);
	fs.writeFileSync(
		resultsPath,
		JSON.stringify(
			{
				inactive: inactiveResults,
				active: activeResults,
			},
			null,
			2
		)
	);
	printComparison(
		'Woo active vs inactive editor metrics',
		activeComparison,
		'inactive',
		'active'
	);
	console.log( `\nSaved comparison to ${ comparisonPath }` );
	console.log( `Saved raw results to ${ resultsPath }` );
}

main();
