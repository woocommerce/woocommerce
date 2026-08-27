#!/usr/bin/env node
/**
 * Build script for the classic (non-React) WooCommerce assets.
 *
 *   js:  copy sources into ../../assets/js, concat the admin utils into
 *        wc-shipping-zone-methods.js, minify everything to .min.js (uglify-es).
 *   css: compile Sass (sass-embedded), generate RTL variants (rtlcss),
 *        minify in place (clean-css), prepend select2.css onto admin(-rtl).css,
 *        then move/copy the results into ../../assets/css.
 *
 * The library versions (pinned exactly in package.json) and the options
 * passed to them are chosen so the built files are byte-identical to the
 * output of the Grunt pipeline this script replaced. Changing either
 * changes the shipped assets, so treat both as part of the contract.
 *
 * Usage: node build.js [js|css|watch]  (no argument builds js and css)
 */

'use strict';

const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const {
	Worker,
	isMainThread,
	parentPort,
} = require( 'worker_threads' );

const CSS_SRC = path.join( __dirname, 'css' );
const JS_SRC = path.join( __dirname, 'js' );
const CSS_DEST = path.join( __dirname, '..', '..', 'assets', 'css' );
const JS_DEST = path.join( __dirname, '..', '..', 'assets', 'js' );

/* ------------------------------------------------------------------ */
/* Small fs helpers                                                    */
/* ------------------------------------------------------------------ */

// Read a text file, stripping a UTF-8 BOM so it can't leak into
// concatenated or minified output.
function read( file ) {
	let content = fs.readFileSync( file, 'utf8' );
	if ( content.charCodeAt( 0 ) === 0xfeff ) {
		content = content.slice( 1 );
	}
	return content;
}

function write( file, content ) {
	fs.mkdirSync( path.dirname( file ), { recursive: true } );
	fs.writeFileSync( file, content );
}

function copyFile( src, dest ) {
	fs.mkdirSync( path.dirname( dest ), { recursive: true } );
	fs.copyFileSync( src, dest );
}

function moveFile( src, dest ) {
	fs.mkdirSync( path.dirname( dest ), { recursive: true } );
	fs.renameSync( src, dest );
}

// List plain files directly inside dir (no recursion, no dotfiles).
function listFiles( dir ) {
	if ( ! fs.existsSync( dir ) ) {
		return [];
	}
	return fs
		.readdirSync( dir, { withFileTypes: true } )
		.filter( ( e ) => e.isFile() && ! e.name.startsWith( '.' ) )
		.map( ( e ) => e.name )
		.sort();
}

// List files under dir recursively, as paths relative to dir (no dotfiles).
function walk( dir, prefix = '' ) {
	if ( ! fs.existsSync( dir ) ) {
		return [];
	}
	const out = [];
	for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		if ( entry.name.startsWith( '.' ) ) {
			continue;
		}
		const rel = prefix ? prefix + '/' + entry.name : entry.name;
		if ( entry.isDirectory() ) {
			out.push( ...walk( path.join( dir, entry.name ), rel ) );
		} else if ( entry.isFile() ) {
			out.push( rel );
		}
	}
	return out.sort();
}

/* ------------------------------------------------------------------ */
/* JS pipeline                                                         */
/* ------------------------------------------------------------------ */

// True when dest is missing or older than src, so unchanged files can
// be skipped on watch-mode rebuilds.
function isStale( src, dest ) {
	return (
		! fs.existsSync( dest ) ||
		fs.statSync( dest ).mtimeMs < fs.statSync( src ).mtimeMs
	);
}

// Copy js/** (minus the concat-only utils and tests) into assets/js,
// plus the sourcebuster dist files. When stale === true, only files
// newer than their copy are written.
function copyJs( { stale = false } = {} ) {
	const copy = ( src, dest ) => {
		if ( ! stale || isStale( src, dest ) ) {
			copyFile( src, dest );
		}
	};

	for ( const rel of walk( JS_SRC ) ) {
		const dirs = rel.split( '/' ).slice( 0, -1 );
		if ( rel.startsWith( 'admin/utils/' ) || dirs.includes( 'test' ) ) {
			continue;
		}
		copy( path.join( JS_SRC, rel ), path.join( JS_DEST, rel ) );
	}

	const sourcebuster = path.join( __dirname, 'node_modules', 'sourcebuster' );
	for ( const name of listFiles( path.join( sourcebuster, 'dist' ) ) ) {
		if ( name.startsWith( 'sourcebuster' ) ) {
			copy(
				path.join( sourcebuster, 'dist', name ),
				path.join( JS_DEST, 'sourcebuster', name )
			);
		}
	}
	copy(
		path.join( sourcebuster, 'LICENSE' ),
		path.join( JS_DEST, 'sourcebuster', 'LICENSE' )
	);
}

// Prepend the number validation and maybe-modify-decimal utils onto
// wc-shipping-zone-methods.js. Always rebuilt from the source, so its
// .min.js is re-minified on every watch pass (cheap: one small file).
function concatJs() {
	const dest = path.join( JS_DEST, 'admin', 'wc-shipping-zone-methods.js' );
	copyFile(
		path.join( JS_SRC, 'admin', 'wc-shipping-zone-methods.js' ),
		dest
	);
	const parts = [
		path.join( JS_SRC, 'admin', 'utils', 'number-validation.js' ),
		path.join( JS_SRC, 'admin', 'utils', 'maybe-modify-decimal.js' ),
		dest,
	];
	write( dest, parts.map( read ).join( '\n' ) );
}

function minifyJsFile( src, dest ) {
	const uglify = require( 'uglify-es' );
	const result = uglify.minify(
		{ [ path.basename( src ) ]: read( src ) },
		{
			compress: {},
			ie8: true,
			mangle: { reserved: [] },
			output: { comments: /@license|@preserve|^!/ },
			parse: { strict: false },
		}
	);
	if ( result.error ) {
		throw new Error( `Minifying ${ src } failed: ${ result.error }` );
	}
	write( dest, result.code );
}

// Minification is CPU-bound and per-file independent, so batches are
// spread over a pool of worker threads (each worker runs this file
// with isMainThread === false and minifies the jobs it is sent).
function minifyJsParallel( jobs ) {
	const poolSize = Math.min(
		jobs.length,
		Math.max( 1, os.availableParallelism() - 1 )
	);
	const queue = [ ...jobs ];
	return Promise.all(
		Array.from( { length: poolSize }, () => {
			return new Promise( ( resolve, reject ) => {
				const worker = new Worker( __filename );
				const next = () => {
					const job = queue.shift();
					if ( ! job ) {
						worker.terminate().then( resolve, reject );
						return;
					}
					worker.postMessage( job );
				};
				worker.on( 'message', next );
				worker.on( 'error', reject );
				next();
			} );
		} )
	);
}

// Minify every non-minified .js in assets/js to a .min.js sibling.
// When stale === true, skip files whose .min.js is already up to date.
async function minifyJs( { stale = false } = {} ) {
	const jobs = [];
	for ( const rel of walk( JS_DEST ) ) {
		if ( ! rel.endsWith( '.js' ) || rel.endsWith( '.min.js' ) ) {
			continue;
		}
		const src = path.join( JS_DEST, rel );
		const dest = src.replace( /\.js$/, '.min.js' );
		if ( stale && ! isStale( src, dest ) ) {
			continue;
		}
		jobs.push( { src, dest } );
	}
	// Worker startup costs ~50ms each, so small batches (watch mode
	// rebuilds) are faster minified in-process.
	if ( jobs.length < 4 ) {
		jobs.forEach( ( { src, dest } ) => minifyJsFile( src, dest ) );
	} else {
		await minifyJsParallel( jobs );
	}
}

async function buildJs( options = {} ) {
	const started = Date.now();
	copyJs( options );
	concatJs();
	await minifyJs( options );
	console.log( `js built in ${ Date.now() - started }ms` );
}

/* ------------------------------------------------------------------ */
/* CSS pipeline                                                        */
/* ------------------------------------------------------------------ */

// Compile every non-partial css/*.scss to css/*.css.
async function compileSass() {
	const sass = require( 'sass-embedded' );
	await Promise.all(
		listFiles( CSS_SRC )
			.filter( ( f ) => f.endsWith( '.scss' ) && ! f.startsWith( '_' ) )
			.map( async ( f ) => {
				const result = await sass.compileAsync(
					path.join( CSS_SRC, f ),
					{ sourceMap: false }
				);
				write(
					path.join( CSS_SRC, f.replace( /\.scss$/, '.css' ) ),
					result.css
				);
			} )
	);
}

// Generate css/*-rtl.css for every compiled stylesheet except select2.css.
function generateRtl() {
	const rtlcss = require( 'rtlcss' );
	const processor = rtlcss.configure( {
		options: {
			autoRename: false,
			autoRenameStrict: false,
			blacklist: {},
			clean: true,
			greedy: false,
			processUrls: false,
			stringMap: [],
		},
		plugins: [],
	} );
	for ( const f of listFiles( CSS_SRC ) ) {
		if (
			! f.endsWith( '.css' ) ||
			f === 'select2.css' ||
			f.endsWith( '-rtl.css' )
		) {
			continue;
		}
		const src = path.join( CSS_SRC, f );
		const dest = path.join(
			CSS_SRC,
			f.replace( /\.css$/, '-rtl.css' )
		);
		write( dest, processor.process( read( src ), { map: false, from: src, to: dest } ).css );
	}
}

// Minify one stylesheet with clean-css. The file is passed by path:
// string input would be treated differently (no source directory context).
function minifyCssFile( src, dest ) {
	const CleanCSS = require( 'clean-css' );
	const result = new CleanCSS( { sourceMap: false } ).minify( [ src ] );
	if ( result.errors.length ) {
		throw new Error( `Minifying ${ src } failed: ${ result.errors }` );
	}
	result.warnings.forEach( ( warning ) =>
		console.warn( `clean-css [${ path.basename( src ) }]: ${ warning }` )
	);
	write( dest, result.styles );
}

// Minify css/*.css in place, and the photoswipe stylesheets to .min.css.
function minifyCss() {
	for ( const f of listFiles( CSS_SRC ) ) {
		if ( f.endsWith( '.css' ) ) {
			const file = path.join( CSS_SRC, f );
			minifyCssFile( file, file );
		}
	}
	for ( const dir of [ 'photoswipe', 'photoswipe/default-skin' ] ) {
		for ( const f of listFiles( path.join( CSS_SRC, dir ) ) ) {
			if ( f.endsWith( '.css' ) && ! f.endsWith( '.min.css' ) ) {
				minifyCssFile(
					path.join( CSS_SRC, dir, f ),
					path.join(
						CSS_SRC,
						dir,
						f.replace( /\.css$/, '.min.css' )
					)
				);
			}
		}
	}
}

// Prepend select2.css onto admin.css and admin-rtl.css.
function concatCss() {
	for ( const admin of [ 'admin.css', 'admin-rtl.css' ] ) {
		const dest = path.join( CSS_SRC, admin );
		write(
			dest,
			[ path.join( CSS_SRC, 'select2.css' ), dest ]
				.map( read )
				.join( '\n' )
		);
	}
}

// The css steps above process files in place inside css/, so move the
// results to assets/css and copy the static sources alongside them.
function moveAndCopyCss() {
	for ( const f of listFiles( CSS_SRC ) ) {
		if ( f.endsWith( '.css' ) ) {
			moveFile( path.join( CSS_SRC, f ), path.join( CSS_DEST, f ) );
		}
	}
	for ( const dir of [ 'photoswipe', 'photoswipe/default-skin' ] ) {
		for ( const f of listFiles( path.join( CSS_SRC, dir ) ) ) {
			if ( f.endsWith( '.min.css' ) ) {
				moveFile(
					path.join( CSS_SRC, dir, f ),
					path.join( CSS_DEST, dir, f )
				);
			}
		}
	}
	for ( const dir of [ 'photoswipe', 'jquery-ui' ] ) {
		for ( const rel of walk( path.join( CSS_SRC, dir ) ) ) {
			copyFile(
				path.join( CSS_SRC, dir, rel ),
				path.join( CSS_DEST, dir, rel )
			);
		}
	}
	for ( const f of listFiles( CSS_SRC ) ) {
		if ( f.endsWith( '.scss' ) ) {
			copyFile( path.join( CSS_SRC, f ), path.join( CSS_DEST, f ) );
		}
	}
}

async function buildCss() {
	const started = Date.now();
	await compileSass();
	generateRtl();
	minifyCss();
	concatCss();
	moveAndCopyCss();
	console.log( `css built in ${ Date.now() - started }ms` );
}

/* ------------------------------------------------------------------ */
/* Watch mode                                                          */
/* ------------------------------------------------------------------ */

function watch() {
	const chokidar = require( 'chokidar' );

	let running = false;
	const pending = new Set();
	const runQueued = async ( task ) => {
		if ( task ) {
			pending.add( task );
		}
		if ( running || pending.size === 0 ) {
			return;
		}
		running = true;
		const next = [ ...pending ];
		pending.clear();
		for ( const t of next ) {
			try {
				if ( t === 'css' ) {
					await buildCss();
				} else {
					// Re-minify only files whose .min.js is out of date.
					await buildJs( { stale: true } );
				}
			} catch ( error ) {
				console.error( error );
			}
		}
		running = false;
		// Drain anything queued while we were building.
		runQueued();
	};

	chokidar
		.watch( [ 'css/*.scss' ], { cwd: __dirname, ignoreInitial: true } )
		.on( 'all', () => runQueued( 'css' ) );

	chokidar
		.watch( [ 'js/**/*.js' ], {
			cwd: __dirname,
			ignoreInitial: true,
			ignored: '**/*.min.js',
		} )
		.on( 'all', () => runQueued( 'js' ) );

	console.log( 'Watching css/ and js/ for changes...' );
}

/* ------------------------------------------------------------------ */
/* Entry point                                                         */
/* ------------------------------------------------------------------ */

async function main() {
	const task = process.argv[ 2 ] || 'all';
	switch ( task ) {
		case 'js':
			await buildJs();
			break;
		case 'css':
			await buildCss();
			break;
		case 'all':
			await Promise.all( [ buildJs(), buildCss() ] );
			break;
		case 'watch':
			watch();
			break;
		default:
			console.error( `Unknown task: ${ task }` );
			process.exitCode = 1;
	}
}

if ( isMainThread ) {
	main().catch( ( error ) => {
		console.error( error );
		process.exitCode = 1;
	} );
} else {
	// Worker thread: minify the { src, dest } jobs the pool sends over.
	parentPort.on( 'message', ( { src, dest } ) => {
		minifyJsFile( src, dest );
		parentPort.postMessage( 'done' );
	} );
}
