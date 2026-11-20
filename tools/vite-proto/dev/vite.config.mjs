/**
 * Vite dev server for the wc-admin `app` entry.
 *
 * WordPress still enqueues app/index.js as a classic script. In dev that file
 * is a stub (dev/stub.js) that adds the Vite client and client/index.tsx as
 * ES modules from this server. WordPress and WooCommerce packages stay window
 * globals, loaded by the scripts PHP already enqueues.
 */
import path from 'node:path';
import { createRequire } from 'node:module';
import { defineConfig, transformWithOxc } from 'vite';
import react from '@vitejs/plugin-react';
import { parseAst } from 'rolldown/parseAst';
import { ADMIN, PACKAGES, REPO } from '../lib/shared.mjs';
import { adminExternals } from '../configs/admin.mjs';

const adminRequire = createRequire( path.join( ADMIN, 'package.json' ) );
const globalExpression = ( parts ) =>
	'window' + [].concat( parts ).map( ( p ) => `[${ JSON.stringify( p ) }]` ).join( '' );
const interop = ( g ) => `(${ g } && ${ g }.__esModule ? ${ g }.default : ${ g })`;
const GLOBAL = '\0wc-global:';

/** Source files: turn imports of externals into reads of window globals. */
function externalImports( code, id ) {
	let ast;
	try {
		ast = parseAst( code, { lang: /\.tsx?$/.test( id ) ? 'tsx' : 'jsx' } );
	} catch {
		return null;
	}
	const edits = [];
	let head = '';
	for ( const node of ast.body ) {
		const source = node.source?.value;
		const parts = source && adminExternals.toGlobal( source );
		if ( ! parts ) {
			continue;
		}
		const g = globalExpression( parts );
		if ( node.type === 'ImportDeclaration' ) {
			if ( node.importKind === 'type' ) {
				continue;
			}
			const named = [];
			for ( const spec of node.specifiers ) {
				if ( spec.importKind === 'type' ) {
					continue;
				}
				if ( spec.type === 'ImportDefaultSpecifier' ) {
					head += `const ${ spec.local.name } = ${ interop( g ) };`;
				} else if ( spec.type === 'ImportNamespaceSpecifier' ) {
					head += `const ${ spec.local.name } = ${ g };`;
				} else {
					const imported = spec.imported.name ?? spec.imported.value;
					named.push(
						imported === spec.local.name
							? imported
							: `${ JSON.stringify( imported ) }: ${ spec.local.name }`
					);
				}
			}
			if ( named.length ) {
				head += `const { ${ named.join( ', ' ) } } = ${ g };`;
			}
			edits.push( [ node.start, node.end, '' ] );
		} else if ( node.type === 'ExportAllDeclaration' || node.type === 'ExportNamedDeclaration' ) {
			throw new Error( `Re-export of an external is not handled: ${ id }` );
		}
	}
	if ( ! edits.length ) {
		return null;
	}
	for ( const [ start, end, text ] of edits.sort( ( a, b ) => b[ 0 ] - a[ 0 ] ) ) {
		// Keep the line count so stack traces still match.
		const lines = code.slice( start, end ).replace( /[^\n]/g, '' );
		code = code.slice( 0, start ) + text + lines + code.slice( end );
	}
	return { code: head + code, map: null };
}

const wcGlobals = () => ( {
	name: 'wc-globals',
	enforce: 'pre',
	transform: {
		filter: { id: { include: /\.[jt]sx?$/, exclude: /node_modules/ } },
		handler( code, id ) {
			return externalImports( code, id.split( '?' )[ 0 ] );
		},
	},
} );

/** Dependencies (pre-bundled): externals become tiny CommonJS modules. */
const wcGlobalsForDeps = () => ( {
	name: 'wc-globals-deps',
	resolveId( source ) {
		return adminExternals.toGlobal( source ) ? GLOBAL + source : null;
	},
	load( id ) {
		if ( ! id.startsWith( GLOBAL ) ) {
			return null;
		}
		const g = globalExpression( adminExternals.toGlobal( id.slice( GLOBAL.length ) ) );
		return { code: `module.exports = ${ g };`, moduleType: 'js' };
	},
} );

/** The admin client keeps JSX in .js files. */
const jsxInJs = () => ( {
	name: 'wc-jsx-in-js',
	enforce: 'pre',
	transform: {
		filter: { id: { include: /\.js$/, exclude: /node_modules/ } },
		async handler( code, id ) {
			if ( ! id.startsWith( REPO ) ) {
				return null;
			}
			return transformWithOxc( code, id, {
				lang: 'jsx',
				jsx: { runtime: 'automatic', development: true, refresh: true },
			} );
		},
	},
} );

const PRELUDE =
	'@use "sass:math";@import "_colors"; @import "_variables"; @import "_breakpoints"; @import "_mixins"; ';

export default defineConfig( {
	root: ADMIN,
	base: '/',
	cacheDir: path.join( import.meta.dirname, '.vite' ),
	plugins: [ wcGlobals(), jsxInJs(), react() ],
	define: {
		'process.env.NODE_ENV': '"development"',
		__i18n_text_domain__: '"woocommerce"',
		global: 'globalThis',
	},
	resolve: {
		alias: [
			{ find: /^~\//, replacement: path.join( ADMIN, 'client' ) + '/' },
			{ find: 'react/jsx-dev-runtime', replacement: adminRequire.resolve( 'react/jsx-dev-runtime' ) },
			{ find: 'react/jsx-runtime', replacement: adminRequire.resolve( 'react/jsx-runtime' ) },
		],
		conditions: [ 'wc-source', 'browser', 'module', 'development' ],
		extensions: [ '.json', '.js', '.jsx', '.ts', '.tsx' ],
	},
	assetsInclude: [ '**/*.md' ],
	css: {
		preprocessorOptions: {
			scss: {
				additionalData: ( content, file ) =>
					file.includes( '@automattic+' ) ? content : PRELUDE + content,
				loadPaths: [ path.join( PACKAGES, 'internal-build/styles' ), ADMIN ],
				silenceDeprecations: [ 'import', 'global-builtin', 'legacy-js-api', 'mixed-decls', 'slash-div', 'color-functions' ],
				quietDeps: true,
			},
		},
	},
	optimizeDeps: {
		entries: [ 'client/index.tsx' ],
		rolldownOptions: { plugins: [ wcGlobalsForDeps() ], moduleTypes: { '.js': 'jsx' } },
	},
	server: {
		host: '127.0.0.1',
		port: 5173,
		strictPort: true,
		cors: true,
		origin: 'http://localhost:5173',
		fs: { allow: [ REPO ] },
	},
} );
