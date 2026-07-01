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
const defaultRounds = 3;
const roundsArg = process.argv.find( ( arg ) => arg.startsWith( '--rounds=' ) );
const rounds = parseInt(
	( roundsArg && roundsArg.split( '=' )[ 1 ] ) ||
		process.env.WC_EDITOR_COMPARISON_ROUNDS ||
		process.env.ROUNDS ||
		defaultRounds,
	10
);

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
	return runCommand(
		'pnpm',
		[ 'wp-env', 'run', 'tests-cli', 'wp', ...args ],
		options
	);
}

function readResults( resultsId ) {
	const resultsPath = path.join(
		artifactsPath,
		`${ resultsId }.performance-results.json`
	);

	return JSON.parse( fs.readFileSync( resultsPath, 'utf8' ) );
}

function runEditorMetrics( stateId, round ) {
	const resultsId = `editor_${ stateId }_round-${ round }`;

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
				RESULTS_ID: resultsId,
			},
		}
	);

	return readResults( resultsId );
}

function getPluginStatus() {
	try {
		const output = wpCli(
			[ 'plugin', 'list', '--name=woocommerce', '--field=status' ],
			{
				stdio: 'pipe',
				logCommand: false,
			}
		);
		return output.trim() || 'unknown';
	} catch ( error ) {
		return 'unknown';
	}
}

function setWooCommercePluginState( state ) {
	if ( state === 'active' ) {
		wpCli( [ 'plugin', 'activate', 'woocommerce' ] );
		return;
	}

	if ( state === 'inactive' ) {
		wpCli( [ 'plugin', 'deactivate', 'woocommerce' ] );
		return;
	}

	wpCli( [ 'plugin', 'activate', 'woocommerce' ] );
}

function median( values ) {
	if ( ! values.length ) {
		return null;
	}

	const sorted = [ ...values ].sort( ( a, b ) => a - b );
	const middleIndex = Math.floor( sorted.length / 2 );

	if ( sorted.length % 2 === 0 ) {
		return ( sorted[ middleIndex - 1 ] + sorted[ middleIndex ] ) / 2;
	}

	return sorted[ middleIndex ];
}

function mean( values ) {
	if ( ! values.length ) {
		return null;
	}

	return (
		values.reduce( ( total, value ) => total + value, 0 ) / values.length
	);
}

function roundNumber( value ) {
	if ( value === null || value === undefined || Number.isNaN( value ) ) {
		return null;
	}

	return Math.round( value * 100 ) / 100;
}

function collectSamples( roundsResults, state ) {
	const samples = {};

	for ( const roundResults of roundsResults ) {
		const stateResults = roundResults[ state ];

		for ( const [ metric, value ] of Object.entries( stateResults ) ) {
			if ( typeof value !== 'number' ) {
				continue;
			}

			samples[ metric ] = samples[ metric ] || [];
			samples[ metric ].push( value );
		}
	}

	return samples;
}

function aggregateResults( roundsResults, state ) {
	const samples = collectSamples( roundsResults, state );
	const aggregate = {};

	for ( const [ metric, values ] of Object.entries( samples ) ) {
		aggregate[ metric ] = {
			median: roundNumber( median( values ) ),
			mean: roundNumber( mean( values ) ),
			samples: values.map( roundNumber ),
		};
	}

	return aggregate;
}

function getDeltaPercent( base, target ) {
	if ( base === null || base === undefined ) {
		return null;
	}

	if ( base === 0 ) {
		return target === 0 ? 0 : null;
	}

	return ( ( target - base ) / base ) * 100;
}

function buildComparison( baseResults, targetResults, baseLabel, targetLabel ) {
	const comparison = {};
	const metrics = new Set( [
		...Object.keys( baseResults ),
		...Object.keys( targetResults ),
	] );

	for ( const metric of metrics ) {
		const base = baseResults[ metric ] || {};
		const target = targetResults[ metric ] || {};
		const medianDelta = target.median - base.median;
		const meanDelta = target.mean - base.mean;
		const medianDeltaPercent = getDeltaPercent(
			base.median,
			target.median
		);
		const meanDeltaPercent = getDeltaPercent( base.mean, target.mean );

		comparison[ metric ] = {
			[ baseLabel ]: base,
			[ targetLabel ]: target,
			medianDelta: roundNumber( medianDelta ),
			medianDeltaPercent: roundNumber( medianDeltaPercent ),
			meanDelta: roundNumber( meanDelta ),
			meanDeltaPercent: roundNumber( meanDeltaPercent ),
		};
	}

	return comparison;
}

function getMetricName( metric ) {
	const parts = metric.split( '.' );
	return parts[ parts.length - 1 ];
}

function isWooAttributionMetric( metric ) {
	const metricName = getMetricName( metric );
	return (
		metricName.startsWith( 'woo' ) || metricName === 'registeredWooBlocks'
	);
}

function formatPercent( value ) {
	return value === null ? 'n/a' : `${ value.toFixed( 2 ) }%`;
}

function formatValue( value ) {
	return value === null || value === undefined ? 'n/a' : value;
}

function printComparison( title, comparison, baseLabel, targetLabel, filter ) {
	const printable = {};

	for ( const [ metric, values ] of Object.entries( comparison ) ) {
		if ( filter && ! filter( metric ) ) {
			continue;
		}

		printable[ metric ] = {
			[ `${ baseLabel } median` ]: formatValue(
				values[ baseLabel ].median
			),
			[ `${ targetLabel } median` ]: formatValue(
				values[ targetLabel ].median
			),
			medianDelta: formatValue( values.medianDelta ),
			medianDeltaPercent: formatPercent( values.medianDeltaPercent ),
			[ `${ baseLabel } mean` ]: formatValue( values[ baseLabel ].mean ),
			[ `${ targetLabel } mean` ]: formatValue(
				values[ targetLabel ].mean
			),
			meanDelta: formatValue( values.meanDelta ),
			meanDeltaPercent: formatPercent( values.meanDeltaPercent ),
		};
	}

	if ( ! Object.keys( printable ).length ) {
		return;
	}

	console.log( `\n${ title }\n` );
	console.table( printable );
}

function getLargestTimingDelta( comparison ) {
	return Object.entries( comparison )
		.filter( ( [ metric, values ] ) => {
			return (
				! isWooAttributionMetric( metric ) &&
				typeof values.medianDelta === 'number'
			);
		} )
		.sort(
			( [ , first ], [ , second ] ) =>
				Math.abs( second.medianDelta ) - Math.abs( first.medianDelta )
		)[ 0 ];
}

function printSummary( comparison ) {
	const largestTimingDelta = getLargestTimingDelta( comparison );

	console.log( '\nSummary\n' );
	if ( largestTimingDelta ) {
		const [ metric, values ] = largestTimingDelta;
		console.log(
			`Largest timing median delta: ${
				values.medianDelta
			} (${ formatPercent( values.medianDeltaPercent ) }) for ${ metric }`
		);
	}

	for ( const metricCandidates of [
		[ 'emptyEditor.wooEncodedBodySize', 'wooEncodedBodySize' ],
		[ 'emptyEditor.wooResources', 'wooResources' ],
		[ 'emptyEditor.registeredWooBlocks', 'registeredWooBlocks' ],
	] ) {
		const metric = metricCandidates.find(
			( candidate ) => comparison[ candidate ]
		);

		if ( metric ) {
			console.log(
				`${ metric } median delta: ${ comparison[ metric ].medianDelta }`
			);
		}
	}
}

function main() {
	if ( ! Number.isInteger( rounds ) || rounds < 1 ) {
		throw new Error( 'Rounds must be a positive integer.' );
	}

	fs.mkdirSync( artifactsPath, { recursive: true } );
	console.log( `Writing artifacts to ${ artifactsPath }` );
	console.log( `Running ${ rounds } comparison round(s)` );

	const initialPluginStatus = getPluginStatus();
	console.log(
		`Initial WooCommerce plugin status: ${ initialPluginStatus }`
	);

	const roundsResults = [];

	try {
		for ( let round = 1; round <= rounds; round++ ) {
			const states =
				round % 2 === 1
					? [ 'inactive', 'active' ]
					: [ 'active', 'inactive' ];
			const roundResults = {};

			console.log( `\nRound ${ round } (${ states.join( ' -> ' ) })` );

			for ( const state of states ) {
				setWooCommercePluginState( state );
				roundResults[ state ] = runEditorMetrics(
					`woo-${ state }`,
					round
				);
			}

			roundsResults.push( {
				round,
				order: states,
				inactive: roundResults.inactive,
				active: roundResults.active,
			} );
		}
	} finally {
		setWooCommercePluginState( initialPluginStatus );
	}

	const aggregateResultsByState = {
		inactive: aggregateResults( roundsResults, 'inactive' ),
		active: aggregateResults( roundsResults, 'active' ),
	};
	const activeComparison = buildComparison(
		aggregateResultsByState.inactive,
		aggregateResultsByState.active,
		'inactive',
		'active'
	);
	const comparisonPath = path.join(
		artifactsPath,
		'editor-woo-comparison.json'
	);
	const resultsPath = path.join( artifactsPath, 'editor-woo-results.json' );
	const roundsPath = path.join( artifactsPath, 'editor-woo-rounds.json' );

	fs.writeFileSync(
		comparisonPath,
		JSON.stringify(
			{
				initialPluginStatus,
				rounds,
				aggregate: aggregateResultsByState,
				comparison: activeComparison,
			},
			null,
			2
		)
	);
	fs.writeFileSync(
		resultsPath,
		JSON.stringify( aggregateResultsByState, null, 2 )
	);
	fs.writeFileSync( roundsPath, JSON.stringify( roundsResults, null, 2 ) );
	printComparison(
		'Woo active vs inactive editor timing metrics',
		activeComparison,
		'inactive',
		'active',
		( metric ) => ! isWooAttributionMetric( metric )
	);
	printComparison(
		'Woo active vs inactive loaded asset metrics',
		activeComparison,
		'inactive',
		'active',
		isWooAttributionMetric
	);
	printSummary( activeComparison );
	console.log( `\nSaved comparison to ${ comparisonPath }` );
	console.log( `Saved aggregate results to ${ resultsPath }` );
	console.log( `Saved per-round results to ${ roundsPath }` );
}

main();
