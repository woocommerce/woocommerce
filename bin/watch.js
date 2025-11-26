#!/usr/bin/env node

/**
 * Clean watch script for the WooCommerce monorepo.
 *
 * Features:
 * - Reduced verbosity from wireit and pnpm
 * - Shows file change notifications clearly
 * - Suppresses repetitive output
 *
 * Usage: node bin/watch.js [filter]
 *   filter: Optional package filter (admin, blocks, classic-assets)
 */

const { spawn } = require( 'child_process' );
const readline = require( 'readline' );

// Configuration
const VERBOSE = process.argv.includes( '--verbose' );
const filter = process.argv.find(
	( arg ) => ! arg.startsWith( '-' ) && arg !== process.argv[ 1 ]
);

// ANSI color codes
const colors = {
	reset: '\x1b[0m',
	bright: '\x1b[1m',
	dim: '\x1b[2m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
	red: '\x1b[31m',
	magenta: '\x1b[35m',
};

// Patterns to suppress
const SUPPRESS_PATTERNS = [
	// Wireit internal messages
	/^\s*\d+%\s*\[\d+\s*\/\s*\d+\]/,
	/^Analyzing$/,
	/^\s*$/,
	/\$ wireit$/,
	// Verbose webpack output
	/^asset\s+.*\d+(\.\d+)?\s*[KMG]?i?B/,
	/^\s*\+ \d+ assets$/,
	/^runtime modules/,
	/^orphan modules/,
	/^cacheable modules/,
	/^modules by path/,
	/^\s+javascript modules/,
	/^\s+css modules/,
	/^Entrypoint/,
	// Repetitive completion messages
	/✅ Ran \d+ scripts? and skipped \d+/,
	/^Building \[/,
	/Successfully compiled \d+ files/,
	// TypeScript "Found 0 errors" messages (spam on every recompile)
	/Found 0 errors\. Watching for file changes/,
	// Webpack compiled messages (too verbose, one per entry point)
	/webpack compiled in \d+/,
	/webpack \d+\.\d+\.\d+ compiled successfully/,
	/interactivity.*compiled in \d+/,
	// Dart Sass warnings from external dependencies (cannot fix)
	/DEPRECATION WARNING:.*Using \/ for division/,
	/DEPRECATION WARNING.*Sass/,
	/Recommendation: math\.div/,
	/sass-lang\.com\/d\/slash-div/,
	/^\s*╷\s*$/,
	/^\s*│.*\$grid-unit/,
	/^\s*╵\s*$/,
	/node_modules\/.*@wordpress.*style\.scss/,
];

// Patterns indicating errors
const ERROR_PATTERNS = [
	/\bERROR\b/i,
	/\bError:/,
	/ERR_PNPM/,
	/ELIFECYCLE/,
	/(?<![0 ])\bfailed\b/i,
	/ERROR in/,
	/Cannot find/,
	/FATAL/,
];

// Patterns indicating file changes / rebuilds (show these)
const CHANGE_PATTERNS = [
	/File change detected/i,
	/Compiling\.\.\./,
	/Starting.*compilation/i,
	/rebuilt in/i,
];

// Patterns for warnings (show in yellow)
const WARNING_PATTERNS = [
	/\bWARNING\b/i,
	/\bwarn\b/i,
];

/**
 * Check if content should be suppressed.
 */
function shouldSuppress( content ) {
	if ( VERBOSE ) {
		return false;
	}
	if ( ERROR_PATTERNS.some( ( p ) => p.test( content ) ) ) {
		return false;
	}
	if ( WARNING_PATTERNS.some( ( p ) => p.test( content ) ) ) {
		return false;
	}
	if ( CHANGE_PATTERNS.some( ( p ) => p.test( content ) ) ) {
		return false;
	}
	return SUPPRESS_PATTERNS.some( ( p ) => p.test( content ) );
}

/**
 * Check if content is an error.
 */
function isError( content ) {
	return ERROR_PATTERNS.some( ( p ) => p.test( content ) );
}

/**
 * Check if content indicates a file change.
 */
function isChangeNotification( content ) {
	return CHANGE_PATTERNS.some( ( p ) => p.test( content ) );
}

/**
 * Check if content is a warning.
 */
function isWarning( content ) {
	return WARNING_PATTERNS.some( ( p ) => p.test( content ) );
}

/**
 * Format timestamp.
 */
function timestamp() {
	return new Date().toLocaleTimeString( 'en-US', { hour12: false } );
}

/**
 * Get short package name.
 */
function getShortName( packageName ) {
	return packageName
		.replace( /^\.\.\/\.\.\/packages\/js\//, '' )
		.replace( /^packages\/js\//, '' )
		.replace( /^plugins\/woocommerce\/client\//, '' )
		.replace( /^@woocommerce\//, '' );
}

/**
 * Process a line of output.
 */
function processLine( line ) {
	const cleanLine = line.replace( /\x1b\[[0-9;]*m/g, '' );

	// Parse package prefix (format: "path/to/package task$ content" or "path task: content")
	const match = cleanLine.match(
		/^([\w@.\/-]+)\s+([\w:.-]+)([$:])\s*(.*)$/
	);

	if ( match ) {
		const [ , packagePath, task, , content ] = match;
		const shortName = getShortName( packagePath );

		// Skip empty content
		if ( ! content || ! content.trim() ) {
			return;
		}

		// Check if should suppress
		if ( shouldSuppress( content ) ) {
			return;
		}

		// Format output based on type
		if ( isError( content ) ) {
			console.log(
				`${ colors.red }[${ timestamp() }] ${ shortName }: ${ content }${ colors.reset }`
			);
		} else if ( isWarning( content ) ) {
			console.log(
				`${ colors.yellow }[${ timestamp() }] ${ shortName }: ${ content }${ colors.reset }`
			);
		} else if ( isChangeNotification( content ) ) {
			console.log(
				`${ colors.cyan }[${ timestamp() }] ${ shortName }: ${ content }${ colors.reset }`
			);
		} else if ( VERBOSE ) {
			console.log(
				`${ colors.dim }[${ timestamp() }] ${ shortName }: ${ content }${ colors.reset }`
			);
		}
		return;
	}

	// Non-package lines
	if ( cleanLine.startsWith( 'Scope:' ) ) {
		const scopeMatch = cleanLine.match( /Scope:\s*(\d+)\s*of\s*(\d+)/ );
		if ( scopeMatch ) {
			console.log(
				`${ colors.dim }Watching ${ scopeMatch[ 1 ] } packages...${ colors.reset }`
			);
		}
		return;
	}

	// Skip pnpm headers
	if ( cleanLine.startsWith( '>' ) || cleanLine.trim() === '' ) {
		return;
	}

	// Check for errors in non-package lines
	if ( isError( cleanLine ) ) {
		console.log( `${ colors.red }${ cleanLine }${ colors.reset }` );
		return;
	}

	// Check for warnings in non-package lines
	if ( isWarning( cleanLine ) ) {
		console.log( `${ colors.yellow }${ cleanLine }${ colors.reset }` );
		return;
	}

	// In verbose mode, show everything else
	if ( VERBOSE && cleanLine.trim() ) {
		console.log( `${ colors.dim }${ cleanLine }${ colors.reset }` );
	}
}

/**
 * Main function.
 */
async function main() {
	console.log(
		`${ colors.bright }${ colors.blue }WooCommerce Watch Mode${ colors.reset }`
	);
	if ( filter ) {
		console.log( `${ colors.dim }Filter: ${ filter }${ colors.reset }` );
	}
	console.log(
		`${ colors.dim }Press Ctrl+C to stop${ colors.reset }\n`
	);

	// Determine which watch command to run
	let watchScript = 'watch:build';
	if ( filter === 'admin' ) {
		watchScript = 'watch:build:admin';
	} else if ( filter === 'blocks' ) {
		watchScript = 'watch:build:blocks';
	} else if ( filter === 'classic-assets' ) {
		watchScript = 'watch:build:classic-assets';
	}

	// Set environment variables
	const env = {
		...process.env,
		WIREIT_LOGGER: 'quiet',
		NODE_NO_WARNINGS: '1',
		FORCE_COLOR: '1',
	};

	// Run from plugins/woocommerce directory
	const child = spawn( 'pnpm', [ 'run', watchScript ], {
		cwd: `${ process.cwd() }/plugins/woocommerce`,
		env,
		stdio: [ 'inherit', 'pipe', 'pipe' ],
		shell: false,
	} );

	// Process stdout
	const stdoutRL = readline.createInterface( { input: child.stdout } );
	stdoutRL.on( 'line', processLine );

	// Process stderr
	const stderrRL = readline.createInterface( { input: child.stderr } );
	stderrRL.on( 'line', processLine );

	// Handle exit
	child.on( 'close', ( code ) => {
		console.log(
			`\n${ colors.dim }Watch process exited with code ${ code }${ colors.reset }`
		);
		process.exit( code );
	} );

	// Handle Ctrl+C gracefully
	process.on( 'SIGINT', () => {
		console.log(
			`\n${ colors.yellow }Stopping watch...${ colors.reset }`
		);
		child.kill( 'SIGINT' );
	} );
}

main().catch( ( error ) => {
	console.error( 'Watch script error:', error );
	process.exit( 1 );
} );
