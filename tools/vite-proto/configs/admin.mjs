/**
 * client/admin: one config with scripts, styles, images and lazy chunks.
 * Mirrors plugins/woocommerce/client/admin/webpack.config.js.
 */
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { createRequire } from 'node:module';
import { ADMIN, ASSETS, PACKAGES, REPO, TARGET, externalsFor } from '../lib/shared.mjs';
import { createCss, cssMarkPlugin, orderedMarks } from '../lib/css.mjs';
import { assetPhp } from '../lib/classic.mjs';

export const OUT = path.join( ASSETS, 'admin' );
const adminRequire = createRequire( path.join( ADMIN, 'package.json' ) );
const SETTINGS_UI = path.join( PACKAGES, 'settings-ui' );
const settingsRequire = createRequire( path.join( SETTINGS_UI, 'package.json' ) );

const packageDir = ( name, require ) => path.dirname( require.resolve( `${ name }/package.json` ) );
const dataViewsDir = packageDir( '@wordpress/dataviews', settingsRequire );
const dataViewsRequire = createRequire( path.join( dataViewsDir, 'package.json' ) );
const dataViewsUiDir = packageDir( '@wordpress/ui', dataViewsRequire );
// DataViews' /wp build leaves third-party imports to its own package graph.
const dataViewsRoots = [
	...new Set(
		[
			dataViewsDir,
			packageDir( '@wordpress/components', dataViewsRequire ),
			packageDir( '@wordpress/compose', dataViewsRequire ),
			packageDir( '@wordpress/data', dataViewsRequire ),
			dataViewsUiDir,
			packageDir( '@wordpress/theme', createRequire( path.join( dataViewsUiDir, 'package.json' ) ) ),
		].map( ( dir ) => path.resolve( dir, '../..' ) )
	),
];
const dataViewsWpEntry = settingsRequire.resolve( '@wordpress/dataviews/wp' );
const dataFormRuntime = path.join( SETTINGS_UI, 'src/dataform-runtime.ts' );

const wpAdminScripts = fs
	.readdirSync( path.join( ADMIN, 'client/wp-admin-scripts' ), { withFileTypes: true } )
	.filter( ( entry ) => entry.isDirectory() )
	.map( ( entry ) => entry.name );
const wcAdminPackages = [
	'admin-layout', 'components', 'csv-export', 'currency', 'customer-effort-score', 'date',
	'experimental', 'explat', 'navigation', 'notices', 'number', 'data', 'tracks', 'onboarding',
	'sanitize', 'settings-ui', 'remote-logging', 'email-editor',
];

const camel = ( name ) => name.replace( /-([a-z])/g, ( match, letter ) => letter.toUpperCase() );

function entries() {
	const list = [
		{ name: 'app', files: [ './client/index.tsx' ] },
		{ name: 'embed', files: [ './client/embed.tsx' ] },
	];
	for ( const name of wcAdminPackages ) {
		const dir = path.join( PACKAGES, name );
		const pkg = JSON.parse( fs.readFileSync( path.join( dir, 'package.json' ), 'utf8' ) );
		const source = path.resolve( dir, pkg.exports[ '.' ][ 'wc-source' ] );
		const style = path.join( dir, 'src/style.scss' );
		list.push( {
			name,
			files: [
				fs.existsSync( style ) ? style : null,
				name === 'settings-ui' ? dataFormRuntime : null,
				source,
			].filter( Boolean ),
		} );
	}
	for ( const name of wpAdminScripts ) {
		list.push( { name, files: [ `./client/wp-admin-scripts/${ name }` ] } );
	}
	return list.map( ( entry ) => ( {
		...entry,
		out: wpAdminScripts.includes( entry.name )
			? `wp-admin-scripts/${ entry.name }.js`
			: `${ entry.name }/index.js`,
		library: [ 'wc', camel( entry.name ) ],
	} ) );
}

export const adminExternals = externalsFor(
	( request ) => {
		switch ( request ) {
			case 'moment-timezone':
				return 'moment';
			case 'react/jsx-runtime':
			case 'react/jsx-dev-runtime':
				return null;
			case 'react-dom/client':
				return 'ReactDOM';
			case '@wordpress/global-styles-engine':
				return null;
		}
		if (
			request.startsWith( '@wordpress/dataviews' ) ||
			request.startsWith( '@wordpress/theme' ) ||
			request.startsWith( '@wordpress/ui' ) ||
			/^@wordpress\/.*\/build(?:-module)?/.test( request ) ||
			/^@woocommerce\/.*\/build(?:-module)?/.test( request )
		) {
			return null;
		}
		return undefined;
	},
	( request ) => {
		if ( request === 'moment-timezone' ) {
			return 'moment';
		}
		if ( request === 'react-dom/client' ) {
			return 'react-dom';
		}
		return undefined;
	}
);

const ASSET = /\.(png|jpe?g|gif|svg|eot|ttf|woff|woff2)$/;

// The entries that load lazy chunks share one build. Every other entry is
// its own build, so it only holds the code it uses itself, as in webpack.
const LAZY_ENTRIES = [ 'app', 'embed', 'settings-embed' ];

export function adminConfigs( { outDir = OUT, production = true, sass = 'sass', perEntry = true } = {} ) {
	const css = createCss( 'admin', { production, sass } );
	if ( ! perEntry ) {
		return [ adminConfig( { outDir, production, css } ) ];
	}
	return [
		adminConfig( { outDir, production, css, only: LAZY_ENTRIES } ),
		...entries()
			.filter( ( entry ) => ! LAZY_ENTRIES.includes( entry.name ) )
			.map( ( entry ) =>
				adminConfig( { outDir, production, css, only: [ entry.name ] } )
			),
	];
}

export function adminConfig( { outDir = OUT, production = true, css, only = null } = {} ) {
	const version = ( content ) => createHash( 'sha1' ).update( content ).digest( 'hex' ).slice( 0, 20 );
	return {
		css_: css,
		name: only ? `admin:${ only[ 0 ] }` : 'admin',
		registry: '__wcAdmin',
		root: REPO,
		cwd: ADMIN,
		outDir,
		mode: production ? 'production' : 'development',
		target: TARGET,
		entries: entries().filter( ( entry ) => ! only || only.includes( entry.name ) ),
		injectPolyfill: false,
		publicPath: 'auto',
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx' ],
		alias: {
			'~': path.join( ADMIN, 'client' ),
			'react/jsx-dev-runtime': adminRequire.resolve( 'react/jsx-dev-runtime' ),
			'react/jsx-runtime': adminRequire.resolve( 'react/jsx-runtime' ),
		},
		moduleTypes: { '.md': 'text' },
		lazyFile: ( key ) => `chunks/${ key }.js`,
		// Webpack's splitChunks default: shared async code over 20 KB.
		splitCommon: { minBytes: 20000, file: ( key ) => `chunks/${ key }.js` },
		...adminExternals,
		plugins: [
			{
				name: 'wc-admin-resolve',
				resolveId: {
					filter: { id: /^[^./\0]/ },
					async handler( source, importer ) {
						if ( [ 'crypto', 'fs', 'path' ].includes( source ) ) {
							return '\0wc-empty';
						}
						if ( path.isAbsolute( source ) || source.startsWith( 'wc-entry' ) ) {
							return null;
						}
						if ( source === '@wordpress/dataviews/wp' ) {
							const resolved = await this.resolve( source, importer, { skipSelf: true } );
							return resolved && { ...resolved, moduleSideEffects: true };
						}
						if ( importer === dataViewsWpEntry && ! adminExternals.toGlobal( source ) ) {
							for ( const root of dataViewsRoots ) {
								const resolved = await this.resolve(
									source,
									path.join( root, '..', 'index.js' ),
									{ skipSelf: true }
								);
								if ( resolved ) {
									return resolved;
								}
							}
						}
						return null;
					},
				},
				load: {
					filter: { id: /^\0wc-empty$|\.(png|jpe?g|gif|svg|eot|ttf|woff|woff2)$/ },
					handler( id ) {
						if ( id === '\0wc-empty' ) {
							return { code: 'export default {};', moduleType: 'js' };
						}
						if ( ! ASSET.test( id ) ) {
							return null;
						}
						const url = css.assetUrl( id, null );
						return {
							code: url.startsWith( 'data:' )
								? `export default ${ JSON.stringify( url ) };`
								: `export default __wcPublicPath() + ${ JSON.stringify( url ) };`,
							moduleType: 'js',
						};
					},
				},
			},
			{
				// Oxc rejects what Babel lets through: a type that binds
				// `any` twice. A merge-ready migration fixes the source.
				name: 'wc-admin-source-fixes',
				transform: {
					filter: { id: /experimental-select-control\/selected-items\.tsx$/ },
					handler( code ) {
						return {
							code: code.replace(
								'( { selectedItem: any, index: any } ) =>',
								'( args: { selectedItem: any; index: any } ) =>'
							),
							map: null,
						};
					},
				},
			},
			cssMarkPlugin( css ),
		],
		css: {
			// A lazy chunk with styles gets a stylesheet the loader adds.
			bytes: async ( marks ) => ( await css.join( marks ) ).length,
			lazyFile: ( out, hasStyles ) =>
				hasStyles ? out.replace( /\.js$/, '.style.css' ) : null,
			async plan( { ctx, info, outputs, closure, emit, rootModules } ) {
				for ( const output of outputs.values() ) {
					const { inline } = closure( output.roots, output.kind );
					const allowed = new Set( inline.flatMap( ( f ) => info.get( f ).css ) );
					if ( ! allowed.size ) {
						continue;
					}
					const marks = orderedMarks( ctx, css, rootModules( output ), allowed );
					const file =
						output.kind === 'entry'
							? `${ output.item.entry.name }/style.css`
							: output.out.replace( /\.js$/, '.style.css' );
					const content = await css.join( marks, {
						assetDir: path.posix.relative( path.posix.dirname( file ), '' ) || '.',
					} );
					emit( file, content );
					emit( file.replace( /\.css$/, '-rtl.css' ), css.rtl( content ) );
					if ( output.kind === 'entry' ) {
						emit(
							file.replace( /\.css$/, '.asset.php' ),
							assetPhp( { version: version( content ) } )
						);
					}
				}
				for ( const [ name, content ] of css.assets ) {
					emit( name, content );
				}
			},
		},
	};
}
