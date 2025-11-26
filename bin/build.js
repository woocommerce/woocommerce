#!/usr/bin/env node

/**
 * Clean build script with progress bar for the WooCommerce monorepo.
 *
 * Features:
 * - Single progress bar instead of verbose streaming output
 * - Suppresses common warnings (autoprefixer, sass deprecations, webpack deprecations)
 * - Shows only errors and a summary
 *
 * Usage: node bin/build.js [--verbose]
 */

const { spawn } = require( 'child_process' );
const readline = require( 'readline' );

// Configuration
const PROGRESS_BAR_WIDTH = 40;
const VERBOSE = process.argv.includes( '--verbose' );

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
	bgGreen: '\x1b[42m',
	bgWhite: '\x1b[47m',
};

// Patterns to suppress from output (applied to the content part after package prefix)
// Note: Most warnings have been fixed at source. These remain for:
// - External dependencies we can't modify
// - Verbose but non-actionable output
const SUPPRESS_PATTERNS = [];

// Patterns indicating errors (always show these)
const ERROR_PATTERNS = [
	/\bERROR\b/i,
	/\bError:/,
	/ERR_PNPM/,
	/ELIFECYCLE/,
	/(?<![0 ])\bfailed\b/i, // Match "failed" but not "0 failed" (success message)
	/ERROR in/,
	/Cannot find/,
	/FATAL/,
];

// Patterns indicating warnings
const WARNING_PATTERNS = [
	/\bWARNING\b/i,
	/\bwarn\b/i,
];

// Patterns for package lines (pnpm stream format: "package-name task: content")
const PACKAGE_LINE_PATTERN =
	/^([\w@./-]+)\s+(build:project:[\w-]+)([:$])\s*(.*)?$/;

class BuildRunner {
	constructor() {
		this.startTime = Date.now();
		this.packagesTotal = 0;
		this.packagesComplete = 0;
		this.currentPackages = new Map(); // package -> task
		this.errors = [];
		this.lastProgressLine = '';
		this.seenPackages = new Set();
		this.isTTY = process.stdout.isTTY;
	}

	/**
	 * Check if content should be suppressed.
	 */
	shouldSuppress( content ) {
		if ( VERBOSE ) {
			return false;
		}

		// Always show errors
		if ( ERROR_PATTERNS.some( ( pattern ) => pattern.test( content ) ) ) {
			return false;
		}

		return SUPPRESS_PATTERNS.some( ( pattern ) => pattern.test( content ) );
	}

	/**
	 * Check if content indicates an error.
	 */
	isError( content ) {
		return ERROR_PATTERNS.some( ( pattern ) => pattern.test( content ) );
	}

	/**
	 * Check if content indicates a warning.
	 */
	isWarning( content ) {
		return WARNING_PATTERNS.some( ( pattern ) => pattern.test( content ) );
	}

	/**
	 * Clean up verbose webpack paths in warning/error messages.
	 * Transforms paths like:
	 *   "./assets/js/foo.scss (./assets/js/foo.scss.webpack[...]!=!.../css-loader/...!./assets/js/foo.scss)"
	 * To:
	 *   "./assets/js/foo.scss"
	 */
	cleanWebpackPath( message ) {
		// Remove the verbose webpack loader chain in parentheses
		return message
			.replace( /\s*\([^)]*node_modules[^)]*\)/g, '' )
			.replace( /\.webpack\[javascript\/auto\][^)]*!=!/g, '' );
	}

	/**
	 * Create a progress bar string.
	 */
	createProgressBar( percent ) {
		// Clamp percent to 0-100
		const clampedPercent = Math.min( 100, Math.max( 0, percent ) );
		const filled = Math.round(
			( clampedPercent / 100 ) * PROGRESS_BAR_WIDTH
		);
		const empty = PROGRESS_BAR_WIDTH - filled;
		const bar =
			colors.bgGreen +
			' '.repeat( filled ) +
			colors.reset +
			colors.dim +
			'░'.repeat( empty ) +
			colors.reset;
		return bar;
	}

	/**
	 * Get a short display name for a package.
	 */
	getShortName( packageName ) {
		// Remove common prefixes for cleaner display
		return packageName
			.replace( /^packages\/js\//, '' )
			.replace( /^plugins\/woocommerce\/client\//, '' )
			.replace( /^@woocommerce\//, '' );
	}

	/**
	 * Update the progress display.
	 */
	updateProgress() {
		// Auto-adjust total if we've exceeded our estimate
		if ( this.packagesComplete > this.packagesTotal ) {
			this.packagesTotal =
				this.packagesComplete + this.currentPackages.size;
		}
		const percent =
			this.packagesTotal > 0
				? Math.min(
						100,
						Math.round(
							( this.packagesComplete / this.packagesTotal ) * 100
						)
				  )
				: 0;
		const bar = this.createProgressBar( percent );
		const elapsed = ( ( Date.now() - this.startTime ) / 1000 ).toFixed( 1 );

		// Get current package names (limit to 2 for display)
		const currentList = Array.from( this.currentPackages.keys() )
			.slice( 0, 2 )
			.map( ( p ) => this.getShortName( p ) );
		const moreCount = this.currentPackages.size - currentList.length;
		let currentDisplay =
			currentList.length > 0
				? colors.cyan + currentList.join( ', ' ) + colors.reset
				: colors.dim + 'starting...' + colors.reset;
		if ( moreCount > 0 ) {
			currentDisplay +=
				colors.dim + ` +${ moreCount } more` + colors.reset;
		}

		const progressLine = `${ colors.bright }Building${ colors.reset } ${ bar } ${ colors.green }${ percent }%${ colors.reset } (${ this.packagesComplete }/${ this.packagesTotal }) ${ colors.dim }${ elapsed }s${ colors.reset } ${ currentDisplay }`;

		// Clear the current line and write new progress
		if ( this.isTTY ) {
			// Use \r to return to start of line, then overwrite with spaces and rewrite
			process.stdout.write( '\r\x1b[K' + progressLine );
		}
		this.lastProgressLine = progressLine;
	}

	/**
	 * Print a message above the progress bar.
	 */
	printAboveProgress( message ) {
		if ( this.isTTY ) {
			// Clear current line, print message, then restore progress
			process.stdout.write( '\r\x1b[K' );
			console.log( message );
			this.updateProgress();
		} else {
			console.log( message );
		}
	}

	/**
	 * Process a line of output.
	 */
	processLine( line ) {
		// Strip ANSI codes for pattern matching
		const cleanLine = line.replace( /\x1b\[[0-9;]*m/g, '' );

		// Check for package scope count (e.g., "Scope: 46 of 47 workspace projects")
		const scopeMatch = cleanLine.match( /Scope:\s*(\d+)\s*of\s*(\d+)/ );
		if ( scopeMatch ) {
			// Each package may have ~2 build scripts on average
			this.packagesTotal = parseInt( scopeMatch[ 1 ], 10 ) * 2;
			this.updateProgress();
			return;
		}

		// Check for package line format
		const packageMatch = cleanLine.match( PACKAGE_LINE_PATTERN );
		if ( packageMatch ) {
			const [ , packageName, task, separator, content ] = packageMatch;
			const packageKey = `${ packageName }:${ task }`;

			// Track unique packages
			this.seenPackages.add( packageKey );

			if ( separator === '$' ) {
				// Package starting (e.g., "package build:project:esm$ wireit")
				this.currentPackages.set( packageName, task );
				this.packagesTotal = Math.max(
					this.packagesTotal,
					this.seenPackages.size
				);
				this.updateProgress();
				return;
			}

			// Check for completion
			if ( content && /^(✅|Done)/.test( content ) ) {
				this.currentPackages.delete( packageName );
				this.packagesComplete++;
				this.updateProgress();
				return;
			}

			// Check if content should be shown
			if ( content ) {
				if ( this.isError( content ) ) {
					const cleanContent = this.cleanWebpackPath( content );
					this.errors.push( `${ packageName }: ${ cleanContent }` );
					this.printAboveProgress(
						colors.red +
							`${ packageName }: ${ cleanContent }` +
							colors.reset
					);
					return;
				}

				if ( this.isWarning( content ) ) {
					const cleanContent = this.cleanWebpackPath( content );
					this.printAboveProgress(
						colors.yellow +
							`${ packageName }: ${ cleanContent }` +
							colors.reset
					);
					return;
				}

				if ( ! this.shouldSuppress( content ) ) {
					if ( VERBOSE ) {
						this.printAboveProgress(
							colors.dim +
								`${ packageName }: ${ content }` +
								colors.reset
						);
					}
				}
			}
			return;
		}

		// Non-package lines (pnpm messages, etc.)
		if ( cleanLine.startsWith( '>' ) || cleanLine.startsWith( 'Scope:' ) ) {
			// pnpm header - ignore
			return;
		}

		// Check for errors in non-package lines
		if ( this.isError( cleanLine ) ) {
			const cleanContent = this.cleanWebpackPath( cleanLine );
			this.errors.push( cleanContent );
			this.printAboveProgress( colors.red + cleanContent + colors.reset );
			return;
		}

		// Check for warnings in non-package lines
		if ( this.isWarning( cleanLine ) ) {
			const cleanContent = this.cleanWebpackPath( cleanLine );
			this.printAboveProgress(
				colors.yellow + cleanContent + colors.reset
			);
			return;
		}

		// In verbose mode, show everything
		if ( VERBOSE && cleanLine.trim() ) {
			this.printAboveProgress( colors.dim + cleanLine + colors.reset );
		}
	}

	/**
	 * Run the build.
	 */
	async run() {
		console.log(
			`${ colors.bright }${ colors.blue }WooCommerce Monorepo Build${ colors.reset }\n`
		);

		// Set environment variables to reduce verbosity and suppress warnings
		const env = {
			...process.env,
			// Wireit quiet mode - suppresses progress output
			WIREIT_LOGGER: 'quiet',
			// Suppress Node.js deprecation warnings
			NODE_NO_WARNINGS: '1',
			// Suppress Sass deprecation warnings (Dart Sass 2.0)
			SASS_SILENCE_DEPRECATION_WARNINGS: 'true',
			// Force color output
			FORCE_COLOR: '1',
		};

		// Run pnpm build with --stream to get live output
		const child = spawn(
			'pnpm',
			[
				'-r',
				'--workspace-concurrency=Infinity',
				'--stream',
				'/^build:project:.*$/',
			],
			{
				cwd: process.cwd(),
				env,
				stdio: [ 'inherit', 'pipe', 'pipe' ],
				shell: false,
			}
		);

		this.updateProgress();

		// Process stdout
		const stdoutRL = readline.createInterface( { input: child.stdout } );
		stdoutRL.on( 'line', ( line ) => this.processLine( line ) );

		// Process stderr
		const stderrRL = readline.createInterface( { input: child.stderr } );
		stderrRL.on( 'line', ( line ) => this.processLine( line ) );

		// Wait for completion
		const exitCode = await new Promise( ( resolve ) => {
			child.on( 'close', resolve );
		} );

		// Print final summary
		console.log( '\n\n' );
		const elapsed = ( ( Date.now() - this.startTime ) / 1000 ).toFixed( 1 );

		if ( exitCode === 0 ) {
			console.log(
				`${ colors.green }${ colors.bright }✓ Build completed successfully${ colors.reset } ${ colors.dim }in ${ elapsed }s${ colors.reset }`
			);
			console.log(
				`  ${ colors.dim }${ this.packagesComplete } build tasks completed${ colors.reset }`
			);
		} else {
			console.log(
				`${ colors.red }${ colors.bright }✗ Build failed${ colors.reset } ${ colors.dim }after ${ elapsed }s${ colors.reset }`
			);
			if ( this.errors.length > 0 ) {
				console.log( `\n${ colors.red }Errors:${ colors.reset }` );
				// Deduplicate errors
				const uniqueErrors = [ ...new Set( this.errors ) ];
				uniqueErrors
					.slice( 0, 20 )
					.forEach( ( error ) => console.log( `  ${ error }` ) );
				if ( uniqueErrors.length > 20 ) {
					console.log(
						`  ${ colors.dim }... and ${
							uniqueErrors.length - 20
						} more${ colors.reset }`
					);
				}
			}
		}

		process.exit( exitCode );
	}
}

// Run the build
const runner = new BuildRunner();
runner.run().catch( ( error ) => {
	console.error( 'Build script error:', error );
	process.exit( 1 );
} );
