#!/usr/bin/env node
/**
 * Build output formatter for WooCommerce
 *
 * Runs the build and formats the output to be more readable by:
 * - Showing live progress as packages build
 * - Grouping packages by area (packages/js, client, plugins)
 * - Showing a summary with timing per package
 * - Collecting warnings/errors at the end
 *
 * Usage: node scripts/build-pretty.js
 */

const { spawn } = require( 'child_process' );

// ANSI color codes
const colors = {
	reset: '\x1b[0m',
	bold: '\x1b[1m',
	dim: '\x1b[2m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	red: '\x1b[31m',
	cyan: '\x1b[36m',
	magenta: '\x1b[35m',
};

// Strip ANSI codes from string
function stripAnsi( str ) {
	return str.replace( /\x1b\[[0-9;]*m/g, '' );
}

// Package tracking
const packages = new Map();
const warnings = [];
const errors = [];
let totalPackages = 0;
let completedCount = 0;
const startTime = Date.now();
const currentlyBuilding = new Set();

// Packages that are in scope but don't have build:project scripts
const NO_BUILD_PACKAGES = [
	'dependency-extraction-webpack-plugin',
	'eslint-plugin',
	'eslint-plugin-woocommerce',
	'internal-style-build',
];

// Categorize package by path
function getCategory( path ) {
	if ( path.startsWith( '../../packages/js/' ) ) {
		return 'packages/js';
	}
	if ( path.startsWith( 'client/' ) ) {
		return 'client';
	}
	if ( path.includes( 'woocommerce' ) || path === '.' ) {
		return 'plugins/woocommerce';
	}
	return 'other';
}

// Extract package name from path
function getPackageName( path ) {
	if ( path === '.' ) {
		return 'woocommerce';
	}
	const clean = path
		.replace( '../../packages/js/', '' )
		.replace( 'client/', '' );
	return clean.split( ' ' )[ 0 ];
}

// Parse build type from line
function getBuildType( line ) {
	if ( line.includes( 'build:project:esm' ) ) return 'esm';
	if ( line.includes( 'build:project:cjs' ) ) return 'cjs';
	if ( line.includes( 'build:project:bundle' ) ) return 'bundle';
	if ( line.includes( 'build:project:assets' ) ) return 'assets';
	return null;
}

// Parse timing from completion line
function parseTiming( line ) {
	const match = line.match( /in (\d+(?:\.\d+)?)(m?s)/ );
	if ( match ) {
		const value = parseFloat( match[ 1 ] );
		const unit = match[ 2 ];
		return unit === 'ms' ? value / 1000 : value;
	}
	return 0;
}

// Update the progress line
function updateProgress() {
	const elapsed = ( ( Date.now() - startTime ) / 1000 ).toFixed( 1 );
	const building = [ ...currentlyBuilding ].slice( 0, 3 ).join( ', ' );
	const more =
		currentlyBuilding.size > 3
			? ` +${ currentlyBuilding.size - 3 } more`
			: '';

	// Clear both lines and write progress
	// Move up one line, clear it, then clear current line
	process.stdout.write( `\x1b[2K\x1b[1A\x1b[2K\r` );

	const buildingLabel =
		building.length > 0
			? `${ colors.dim }Building:${ colors.reset } ${ building }${ more }`
			: `${ colors.dim }Starting...${ colors.reset }`;

	process.stdout.write(
		`${ colors.cyan }⏳${ colors.reset } ` +
			`${ colors.bold }${ completedCount }/${ totalPackages || '?' }${
				colors.reset
			} ` +
			buildingLabel +
			`\n` +
			`   ${ colors.dim }Elapsed: ${ elapsed }s${ colors.reset }`
	);
}

// Process a line of output
function processLine( rawLine ) {
	const line = stripAnsi( rawLine );

	// Check for scope line
	const scopeMatch = line.match( /Scope: (\d+) of (\d+) workspace projects/ );
	if ( scopeMatch ) {
		totalPackages = parseInt( scopeMatch[ 1 ], 10 );
		updateProgress();
		return;
	}

	// Track when a package starts building
	if ( line.includes( '$ wireit' ) ) {
		const parts = line.split( ' ' );
		const packageName = getPackageName( parts[ 0 ] );
		currentlyBuilding.add( packageName );
		updateProgress();
		return;
	}

	// Skip other noise lines
	if (
		line.includes( '0% [0 / 1]' ) ||
		line.includes( '100% [1 / 1]' ) ||
		line.includes( 'Analyzing' ) ||
		line.match( /^\s*Done\s*$/ ) ||
		line.includes( 'asset ' ) ||
		line.includes( 'modules' ) ||
		line.includes( 'Entrypoint' ) ||
		line.includes( 'cacheable' ) ||
		line.includes( 'orphan' ) ||
		line.includes( 'runtime modules' ) ||
		line.includes( 'webpack 5' ) ||
		line.includes( 'css ' ) ||
		line.includes( './src/' ) ||
		line.match( /^\s*\+ \d+ assets/ ) ||
		line.match( /^\d{4}-\d{2}-\d{2}/ )
	) {
		return;
	}

	// Capture warnings
	if (
		line.toLowerCase().includes( 'warning' ) ||
		line.includes( 'Browserslist:' ) ||
		line.includes( 'DEPRECATION' )
	) {
		const cleanWarning = line.split( ': ' ).slice( 1 ).join( ': ' ).trim();
		if (
			cleanWarning &&
			! warnings.some( ( w ) =>
				w.includes( cleanWarning.substring( 0, 50 ) )
			)
		) {
			warnings.push( cleanWarning );
		}
		return;
	}

	// Capture errors
	if (
		line.toLowerCase().includes( 'error' ) &&
		! line.includes( 'ERROR in breakpoint' )
	) {
		errors.push( line );
		return;
	}

	// Parse completion lines - format: "path build:project:type: ✅ Ran X scripts..."
	if (
		line.includes( 'Ran' ) &&
		line.includes( 'scripts' ) &&
		line.includes( ' in ' )
	) {
		const parts = line.split( ' ' );
		const path = parts[ 0 ];
		const buildInfo = parts[ 1 ] || '';
		const timing = parseTiming( line );
		const packageName = getPackageName( path );
		const buildType = getBuildType( buildInfo );
		const category = getCategory( path );

		// Remove from currently building
		currentlyBuilding.delete( packageName );

		if ( ! packages.has( packageName ) ) {
			packages.set( packageName, {
				name: packageName,
				category,
				buildTypes: [],
				totalTime: 0,
				cached: false,
			} );
			completedCount++;
		}

		const pkg = packages.get( packageName );
		if ( buildType && ! pkg.buildTypes.includes( buildType ) ) {
			pkg.buildTypes.push( buildType );
		}
		pkg.totalTime = Math.max( pkg.totalTime, timing );

		// Check if cached (skipped means cached)
		if ( line.includes( 'skipped' ) ) {
			pkg.cached = true;
		}

		updateProgress();
	}
}

// Print the formatted output
function printSummary() {
	const totalTime = ( Date.now() - startTime ) / 1000;

	// Clear both progress lines
	process.stdout.write( `\x1b[2K\x1b[1A\x1b[2K\r` );

	console.log( '' );

	console.log(
		`${ colors.bold } WooCommerce Build - ${ packages.size } packages built${ colors.reset } ${ colors.dim }(${ totalPackages } in scope)${ colors.reset }`
	);
	console.log(
		`${ colors.dim }───────────────────────────────────────────────────────────────${ colors.reset }`
	);
	console.log( '' );

	// Group by category
	const categories = {
		'packages/js': [],
		client: [],
		'plugins/woocommerce': [],
		'packages/js (no build)': [],
		other: [],
	};

	// Add no-build packages
	for ( const name of NO_BUILD_PACKAGES ) {
		categories[ 'packages/js (no build)' ].push( {
			name,
			category: 'packages/js (no build)',
			buildTypes: [],
			totalTime: 0,
			noBuild: true,
		} );
	}

	for ( const [ , pkg ] of packages ) {
		categories[ pkg.category ].push( pkg );
	}

	// Print each category
	for ( const [ category, pkgs ] of Object.entries( categories ) ) {
		if ( pkgs.length === 0 ) continue;

		// Sort by name
		pkgs.sort( ( a, b ) => a.name.localeCompare( b.name ) );

		console.log(
			`${ colors.magenta }📦 ${ category }/${ colors.reset } ${ colors.dim }(${ pkgs.length } packages)${ colors.reset }`
		);

		for ( const pkg of pkgs ) {
			let status;
			if ( pkg.noBuild ) {
				status = `${ colors.dim }·  (no build)${ colors.reset }`;
			} else if ( pkg.cached ) {
				status = `${ colors.dim }·  (cached)${ colors.reset }`;
			} else {
				status = `${ colors.green }✅${ colors.reset }`;
			}
			const types =
				pkg.buildTypes.length > 0
					? `(${ pkg.buildTypes.join( ', ' ) })`
					: '';
			const time =
				pkg.cached || pkg.noBuild
					? ''
					: `${ pkg.totalTime.toFixed( 1 ) }s`;

			const namePadded = pkg.name.padEnd( 25 );
			const typesPadded = types.padEnd( 20 );

			console.log(
				`  ${ status } ${ namePadded } ${ colors.dim }${ typesPadded }${ colors.reset } ${ time }`
			);
		}
		console.log( '' );
	}

	console.log(
		`${ colors.dim }───────────────────────────────────────────────────────────────${ colors.reset }`
	);

	if ( errors.length > 0 ) {
		console.log(
			`${ colors.bold }${
				colors.red
			} ❌ Build failed after ${ totalTime.toFixed( 1 ) }s${
				colors.reset
			}`
		);
	} else {
		console.log(
			`${ colors.bold }${
				colors.green
			} ✅ Build completed in ${ totalTime.toFixed( 1 ) }s${
				colors.reset
			}`
		);
	}

	console.log(
		`${ colors.dim }───────────────────────────────────────────────────────────────${ colors.reset }`
	);

	// Print warnings
	if ( warnings.length > 0 ) {
		console.log( '' );
		console.log(
			`${ colors.yellow }⚠️  Warnings: ${ warnings.length }${ colors.reset }`
		);
		const uniqueWarnings = [ ...new Set( warnings ) ].slice( 0, 5 );
		for ( const warning of uniqueWarnings ) {
			const shortWarning = warning.substring( 0, 70 );
			console.log(
				`  ${ colors.dim }- ${ shortWarning }${
					warning.length > 70 ? '...' : ''
				}${ colors.reset }`
			);
		}
		if ( warnings.length > 5 ) {
			console.log(
				`  ${ colors.dim }  ... and ${ warnings.length - 5 } more${
					colors.reset
				}`
			);
		}
	}

	// Print errors
	if ( errors.length > 0 ) {
		console.log( '' );
		console.log(
			`${ colors.red }❌ Errors: ${ errors.length }${ colors.reset }`
		);
		for ( const error of errors.slice( 0, 10 ) ) {
			// Show more of the error message
			console.log( `  ${ colors.red }- ${ error }${ colors.reset }` );
		}
	}

	console.log( '' );
}

// Main
function main() {
	console.log(
		`${ colors.cyan }🔨 Building WooCommerce...${ colors.reset }`
	);
	console.log( '' );
	console.log( '' ); // Extra line for two-line progress display

	// Run the actual build command (same as the original "build" script)
	const build = spawn(
		'pnpm',
		[
			'--if-present',
			'--workspace-concurrency=Infinity',
			'--stream',
			'--filter=@woocommerce/plugin-woocommerce...',
			'/^build:project:.*$/',
		],
		{
			cwd: __dirname + '/..',
			shell: true,
			stdio: [ 'inherit', 'pipe', 'pipe' ],
		}
	);

	let buffer = '';

	const processBuffer = ( data ) => {
		buffer += data.toString();
		const lines = buffer.split( '\n' );
		buffer = lines.pop(); // Keep incomplete line in buffer

		for ( const line of lines ) {
			processLine( line );
		}
	};

	build.stdout.on( 'data', processBuffer );
	build.stderr.on( 'data', processBuffer );

	build.on( 'close', ( code ) => {
		// Process any remaining buffer
		if ( buffer ) {
			processLine( buffer );
		}

		printSummary();

		process.exit( code );
	} );
}

main();
