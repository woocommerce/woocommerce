/**
 * Styles: Sass, PostCSS and RTL, the same steps webpack's loaders run.
 *
 * Rolldown has no CSS pipeline, so a style import becomes an empty module.
 * After bundling, Rolldown's chunk metadata says which style modules ended
 * up in which chunk, and the planner joins them into the files webpack
 * writes.
 */
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { createRequire } from 'node:module';
import { pathToFileURL, fileURLToPath } from 'node:url';
import { REPO, BLOCKS, ADMIN, PACKAGES } from './shared.mjs';

const rootRequire = createRequire( path.join( REPO, 'package.json' ) );
const pnpmDir = ( prefix ) => {
	const base = path.join( REPO, 'node_modules/.pnpm' );
	const match = fs
		.readdirSync( base )
		.filter( ( d ) => d.startsWith( prefix ) )
		.sort()
		.at( -1 );
	return path.join( base, match, 'node_modules' );
};

// `sass` is the JavaScript compiler the repo's sass-loader uses (1.69.5), so
// output can be compared byte for byte. `sass-embedded` is the native
// compiler; it is newer here (1.100.0) and orders some declarations differently.
const sassBlocksRequire = createRequire( path.join( BLOCKS, 'package.json' ) );
let sassModule;
let sassCompiler;
function getSass( flavor ) {
	if ( ! sassCompiler ) {
		if ( flavor === 'sass-embedded' ) {
			sassModule = rootRequire(
				path.join( pnpmDir( 'sass-embedded@' ), 'sass-embedded' )
			);
			sassCompiler = sassModule.initAsyncCompiler();
		} else {
			sassModule = sassBlocksRequire( 'sass' );
			sassCompiler = Promise.resolve( {
				compileStringAsync: async ( source, options ) =>
					sassModule.compileString( source, options ),
				dispose() {},
			} );
		}
	}
	return sassCompiler;
}
export async function disposeSass() {
	if ( sassCompiler ) {
		const compiler = await sassCompiler;
		sassCompiler = null;
		await compiler.dispose();
	}
}

// Resolves `@import "@wordpress/base-styles/colors"` and `~pkg/file` the way
// sass-loader's webpack importer does: from the nearest node_modules.
const nodeModulesImporter = {
	findFileUrl( url, { containingUrl } ) {
		if ( /^(\.|\/|[a-z]+:)/i.test( url ) ) {
			return null;
		}
		const request = url.replace( /^~/, '' );
		const parts = request.split( '/' );
		const name = request.startsWith( '@' )
			? parts.slice( 0, 2 ).join( '/' )
			: parts[ 0 ];
		const rest = request.slice( name.length + 1 );
		let dir = containingUrl
			? path.dirname( fileURLToPath( containingUrl ) )
			: REPO;
		for (;;) {
			const candidate = path.join( dir, 'node_modules', name );
			if ( fs.existsSync( candidate ) ) {
				if ( rest ) {
					return pathToFileURL( path.join( candidate, rest ) );
				}
				const pkg = JSON.parse(
					fs.readFileSync(
						path.join( candidate, 'package.json' ),
						'utf8'
					)
				);
				const main = pkg.sass || pkg.style || pkg.main;
				return main
					? pathToFileURL( path.join( candidate, main ) )
					: null;
			}
			const parent = path.dirname( dir );
			if ( parent === dir ) {
				return null;
			}
			dir = parent;
		}
	},
};

const BLOCKS_PRELUDE =
	'@use "sass:math";@use "sass:string";@use "sass:color";@use "sass:map";' +
	'@import "_colors"; @import "_variables"; @import "_breakpoints"; @import "_mixins"; ';
const ADMIN_PRELUDE =
	'@use "sass:math";@import "_colors"; @import "_variables"; @import "_breakpoints"; @import "_mixins"; ';

const profiles = {
	blocks( production, minifier ) {
		const require = createRequire( path.join( BLOCKS, 'package.json' ) );
		const plugins = [
			require( '@wordpress/theme/postcss-plugins/postcss-ds-token-fallbacks' ).default(),
			require( 'autoprefixer' )( { grid: true } ),
		];
		if ( production && minifier === 'cssnano' ) {
			plugins.push( require( 'cssnano' )() );
		}
		return {
			postcss: require( 'postcss' ),
			plugins,
			rtlcss: require( 'rtlcss' ),
			cssnano: require( 'cssnano' ),
			// The legacy Sass API that sass-loader uses also looks in the working directory.
			loadPaths: [ path.join( BLOCKS, 'assets/css/abstracts' ), BLOCKS ],
			prelude: ( file ) =>
				path
					.relative( BLOCKS, file )
					.startsWith( 'assets/css/abstracts/' )
					? ''
					: BLOCKS_PRELUDE,
			lightning: production && minifier === 'lightningcss',
		};
	},
	admin() {
		const build = path.join( PACKAGES, 'internal-build' );
		const require = createRequire( path.join( build, 'package.json' ) );
		const preset = require( '@wordpress/postcss-plugins-preset' );
		const postcss = createRequire(
			require.resolve( '@wordpress/postcss-plugins-preset' )
		)( 'postcss' );
		return {
			postcss,
			plugins: [
				...preset,
				require( '@wordpress/theme/postcss-plugins/postcss-ds-token-fallbacks' )
					.default,
			],
			rtlcss: require( 'rtlcss' ),
			loadPaths: [ path.join( build, 'styles' ), ADMIN ],
			prelude: ( file ) =>
				file.includes( '@automattic+' ) ? '' : ADMIN_PRELUDE,
			assets: true,
			cssThroughSass: true,
			root: ADMIN,
		};
	},
};

/** A style file's mark: a short hash of its path, stable across builds. */
export const styleMark = ( file ) =>
	createHash( 'sha1' )
		.update( path.relative( REPO, file ) )
		.digest( 'hex' )
		.slice( 0, 12 );

export const isStyle = ( id ) => /\.(s[ac]ss|css)$/.test( id );

export function createCss(
	kind,
	{ production = true, minifier = 'cssnano', sass: sassFlavor = 'sass' } = {}
) {
	const profile = profiles[ kind ]( production, minifier );
	const processor = profile.postcss( profile.plugins );
	const cache = new Map();
	// A mark is a short hash of the file's path, so the built code does
	// not depend on the order files happen to load in.
	const files = new Map();
	const index = new Map();
	const markFor = styleMark;
	const assets = new Map();
	const mtimes = new Map();
	const deps = new Map();
	// Changes when the file or any partial it loaded is written.
	const signature = ( file ) =>
		[ file, ...( deps.get( file ) || [] ) ]
			.map( ( f ) =>
				fs.existsSync( f ) ? fs.statSync( f ).mtimeMs : 0
			)
			.join( ',' );
	const stats = { sassMs: 0, postcssMs: 0, modules: 0 };

	const inlineUrls = ( css, file, outputDir ) =>
		css.replace(
			/url\(\s*(["']?)([^"')]+)\1\s*\)/g,
			( match, quote, url ) => {
				if ( /^(data:|https?:|\/\/|#|\/)/i.test( url ) ) {
					return match;
				}
				const source = path.resolve(
					path.dirname( file ),
					url.split( /[?#]/ )[ 0 ]
				);
				if (
					! fs.existsSync( source ) ||
					! fs.statSync( source ).isFile()
				) {
					return match;
				}
				return `url(${ assetUrl( source, outputDir ) })`;
			}
		);

	// Webpack's `type: 'asset'`: inline below 8 KB, else a hashed file.
	const mime = {
		'.png': 'image/png',
		'.jpg': 'image/jpeg',
		'.jpeg': 'image/jpeg',
		'.gif': 'image/gif',
		'.svg': 'image/svg+xml',
		'.woff': 'font/woff',
		'.woff2': 'font/woff2',
		'.ttf': 'font/ttf',
		'.eot': 'application/vnd.ms-fontobject',
	};
	function assetUrl( source, relativeTo ) {
		const content = fs.readFileSync( source );
		const ext = path.extname( source ).toLowerCase();
		if ( content.length < 8096 ) {
			return `data:${ mime[ ext ] || 'application/octet-stream' };base64,${ content.toString(
				'base64'
			) }`;
		}
		const name =
			createHash( 'sha1' )
				.update( content )
				.digest( 'hex' )
				.slice( 0, 20 ) + ext;
		assets.set( name, content );
		return relativeTo === null ? name : path.posix.join( relativeTo, name );
	}

	async function compile( file ) {
		const started = performance.now();
		let css;
		// The admin style rule sends plain .css through sass-loader too.
		if ( /\.s[ac]ss$/.test( file ) || profile.cssThroughSass ) {
			const sass = await getSass( sassFlavor );
			const source = fs.readFileSync( file, 'utf8' );
			const result = await sass.compileStringAsync(
				profile.prelude( file ) + source,
				{
					url: pathToFileURL( file ),
					syntax: file.endsWith( '.sass' ) ? 'indented' : 'scss',
					loadPaths: profile.loadPaths,
					importers: [ nodeModulesImporter ],
					style: production ? 'compressed' : 'expanded',
					// sass-loader's output has no byte order mark.
					charset: false,
					logger: sassModule.Logger.silent,
					quietDeps: true,
				}
			);
			css = result.css;
			// The partials and imports this file loaded, for watch mode.
			deps.set(
				file,
				result.loadedUrls
					.filter( ( url ) => url.protocol === 'file:' )
					.map( ( url ) => fileURLToPath( url ) )
					.filter( ( dep ) => dep !== file )
			);
		} else {
			css = fs.readFileSync( file, 'utf8' );
		}
		const afterSass = performance.now();
		stats.sassMs += afterSass - started;
		if ( css.trim() ) {
			css = ( await processor.process( css, { from: file, map: false } ) )
				.css;
			// css-loader writes quoted URLs with double quotes.
			css = css.replace(
				/url\('([^']*"[^']*)'\)/g,
				( match, inner ) => `url("${ inner.replace( /"/g, '\\"' ) }")`
			);
			if ( profile.lightning ) {
				css = await lightningMinify( css, file );
			}
		}
		stats.postcssMs += performance.now() - afterSass;
		stats.modules++;
		mtimes.set( file, signature( file ) );
		return css;
	}

	let lightning;
	async function lightningMinify( css, file ) {
		lightning = lightning || ( await import( 'lightningcss' ) );
		return lightning
			.transform( {
				filename: file,
				code: Buffer.from( css ),
				minify: true,
			} )
			.code.toString();
	}

	return {
		stats,
		assets,
		assetUrl,
		/** Register a style file and start compiling it. Returns its mark. */
		mark( file ) {
			if ( ! index.has( file ) ) {
				const id = markFor( file );
				index.set( file, id );
				files.set( id, file );
				const promise = compile( file );
				// A failed compile runs again on the next rebuild.
				promise.catch( () => mtimes.set( file, 'failed' ) );
				cache.set( file, promise );
			}
			return index.get( file );
		},
		fileOf: ( mark ) => files.get( mark ),
		/** The partials a style file loaded, once it has compiled. */
		async depsOf( file ) {
			await cache.get( file )?.catch( () => {} );
			return deps.get( file ) || [];
		},
		markOf: ( file ) => index.get( file ),
		invalidate( file ) {
			// Rolldown loads every module again on a watch rebuild, so only
			// compile a file again when it was written since the last time.
			// Not compiled yet, or still compiling the first time.
			if ( ! mtimes.has( file ) ) {
				return;
			}
			if ( mtimes.get( file ) !== signature( file ) ) {
				mtimes.delete( file );
				const promise = compile( file );
				// A failed compile runs again on the next rebuild.
				promise.catch( () => mtimes.set( file, 'failed' ) );
				cache.set( file, promise );
			}
		},
		/** Join the styles of the given marks into one stylesheet. */
		async join( marks, { assetDir = null } = {} ) {
			let out = '';
			for ( const mark of marks ) {
				let css = await cache.get( files.get( mark ) );
				if ( profile.assets ) {
					css = inlineUrls( css, files.get( mark ), assetDir );
				}
				if ( css ) {
					out += css + '\n';
				}
			}
			return out;
		},
		rtl: ( css ) => profile.rtlcss.process( css, {}, [] ),
		async minifyWhole( css ) {
			if ( minifier === 'lightningcss' ) {
				return lightningMinify( css, 'whole.css' );
			}
			return (
				await profile.postcss( [ profile.cssnano ] ).process( css, {
					from: undefined,
					map: false,
				} )
			).css;
		},
	};
}

/**
 * Style marks in the order MiniCssExtractPlugin writes them: depth first
 * through the imports, a module's styles after the styles it imports.
 */
export function orderedMarks(
	ctx,
	css,
	roots,
	allowed,
	{ dynamic = false } = {}
) {
	const seen = new Set();
	const out = [];
	const later = [];
	const visit = ( id ) => {
		if ( ! id || seen.has( id ) ) {
			return;
		}
		seen.add( id );
		const mark = css.markOf( id );
		if ( mark !== undefined ) {
			if ( allowed.has( mark ) ) {
				out.push( mark );
			}
			return;
		}
		const moduleInfo = ctx.getModuleInfo( id );
		if ( ! moduleInfo ) {
			return;
		}
		moduleInfo.importedIds.forEach( visit );
		if ( dynamic ) {
			later.push( ...moduleInfo.dynamicallyImportedIds );
		}
	};
	roots.forEach( visit );
	while ( later.length ) {
		visit( later.shift() );
	}
	// Anything the walk missed keeps its chunk order.
	for ( const mark of allowed ) {
		if ( ! out.includes( mark ) ) {
			out.push( mark );
		}
	}
	return out;
}

/**
 * Rolldown plugin: a style import becomes a mark, and compiling starts at
 * once, next to the bundling.
 */
export function cssMarkPlugin( css ) {
	return {
		name: 'wc-css-marks',
		load: {
			filter: { id: /\.(s[ac]ss|css)$/ },
			async handler( id ) {
				// A reload in watch mode may mean the file or a partial changed.
				if ( this.meta.watchMode ) {
					if ( css.markOf( id ) !== undefined ) {
						css.invalidate( id );
					}
					css.mark( id );
					// A change to a partial reloads this module too.
					for ( const dep of await css.depsOf( id ) ) {
						this.addWatchFile( dep );
					}
				}
				return {
					// Empty: the linker finds style modules in Rolldown's
					// chunk metadata.
					code: ( css.mark( id ), '' ),
					moduleType: 'js',
					moduleSideEffects: 'no-treeshake',
				};
			},
		},
	};
}
