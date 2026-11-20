/**
 * The parts of the blocks build that are not classic scripts: Interactivity
 * API script modules, the inline dependency detection script, and block.json.
 */
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { rolldown } from 'rolldown';
import {
	BLOCKS,
	TARGET,
	wpModuleExternal,
} from '@woocommerce/rolldown-build/shared';
import { OUT, alias, blocksRequire } from './config.mjs';

const { scriptModuleEntries } = blocksRequire(
	'./bin/interactivity-entries.js'
);
const { genericBlocks } = blocksRequire( './bin/entries.js' );

const moduleEntries = {
	...scriptModuleEntries,
	'woocommerce/product-elements':
		'./assets/js/blocks/product-elements-blocks/frontend.ts',
	'woocommerce/product-sale-badge':
		'./assets/js/blocks/product-elements-blocks/sale-badge/frontend.ts',
	'woocommerce/add-to-cart-with-options-quantity-selector':
		'./assets/js/blocks/add-to-cart-with-options/quantity-selector/frontend.ts',
	'woocommerce/product-collection-cart-reference':
		'./assets/js/blocks/product-collection/cart-reference-frontend.ts',
	'@woocommerce/stores/woocommerce/cart':
		'./assets/js/base/stores/woocommerce/cart.ts',
	'@woocommerce/stores/store-notices':
		'./assets/js/base/stores/store-notices.ts',
	'@woocommerce/stores/woocommerce/products':
		'./assets/js/base/stores/woocommerce/products.ts',
	'@woocommerce/stores/woocommerce/shopper-lists':
		'./assets/js/base/stores/woocommerce/shopper-lists.ts',
};

// Webpack's `module` externals are always static imports; `import` ones stay dynamic.
const externalType = ( request ) => {
	if ( request.startsWith( '@woocommerce/stores/' ) ) {
		return 'module';
	}
	const external = wpModuleExternal( request );
	return external ? external.split( ' ' )[ 0 ] : null;
};

const php = ( value ) => {
	if ( Array.isArray( value ) ) {
		return `array(${ value.map( php ).join( ', ' ) })`;
	}
	if ( value && typeof value === 'object' ) {
		return `array(${ Object.entries( value )
			.map( ( [ k, v ] ) => `'${ k }' => ${ php( v ) }` )
			.join( ', ' ) })`;
	}
	return `'${ value }'`;
};

async function buildModule( name, file, outDir, production, css, watched ) {
	const styles = [];
	const staticDeps = new Set();
	const dynamicDeps = new Set();
	const bundle = await rolldown( {
		cwd: BLOCKS,
		input: { [ name ]: file },
		platform: 'browser',
		tsconfig: false,
		shimMissingExports: true,
		resolve: {
			alias,
			conditionNames: [
				'wc-source',
				'browser',
				'import',
				'module',
				'production',
				'default',
			],
			extensions: [ '.js', '.ts', '.tsx' ],
		},
		moduleTypes: { '.js': 'jsx' },
		transform: {
			target: TARGET,
			jsx: { runtime: 'automatic', development: false },
			define: {
				'process.env.NODE_ENV': production
					? '"production"'
					: '"development"',
				global: 'globalThis',
			},
		},
		logLevel: 'warn',
		checks: { pluginTimings: false },
		onLog( level, log, handler ) {
			if (
				! [
					'EVAL',
					'MODULE_LEVEL_DIRECTIVE',
					'SHIMMED_EXPORT',
				].includes( log.code )
			) {
				handler( level, log );
			}
		},
		plugins: [
			{
				name: 'wc-script-modules',
				resolveId: {
					filter: { id: /^[^./\0]/ },
					handler( source ) {
						if ( path.isAbsolute( source ) ) {
							return null;
						}
						return externalType( source )
							? { id: source, external: true }
							: null;
					},
				},
				transform: {
					filter: { id: /\.[jt]sx?$/, code: /import\(/ },
					handler( code ) {
						// Run a `module` external at load, as webpack does by
						// turning its dynamic import into a static one.
						const hoisted = new Set();
						for ( const match of code.matchAll(
							/\bimport\(\s*(["'])([^"']+)\1\s*\)/g
						) ) {
							if ( externalType( match[ 2 ] ) === 'module' ) {
								hoisted.add( match[ 2 ] );
							}
						}
						if ( ! hoisted.size ) {
							return null;
						}
						return {
							code:
								[ ...hoisted ]
									.map(
										( id ) =>
											`import ${ JSON.stringify( id ) };`
									)
									.join( '' ) + code,
							map: null,
						};
					},
				},
				load: {
					filter: { id: /\.s[ac]ss$/ },
					handler( id ) {
						// On a watch rebuild, compile the style again if it or a partial changed.
						if ( watched && css?.markOf( id ) !== undefined ) {
							css.invalidate( id );
						}
						styles.push( id );
						return { code: '', moduleType: 'js' };
					},
				},
			},
		],
	} );
	const { output } = await bundle.generate( {
		format: 'esm',
		minify: production,
		codeSplitting: false,
		comments: { legal: false, annotation: false, jsdoc: false },
		entryFileNames: '[name].js',
	} );
	if ( watched ) {
		for ( const id of await bundle.watchFiles ) {
			watched.add( id );
		}
		for ( const id of css ? styles : [] ) {
			watched.add( id );
			for ( const dep of await css.depsOf( id ) ) {
				watched.add( dep );
			}
		}
	}
	await bundle.close();
	const chunk = output[ 0 ];
	for ( const id of chunk.imports ) {
		staticDeps.add( id );
	}
	for ( const id of chunk.dynamicImports ) {
		if ( ! staticDeps.has( id ) ) {
			dynamicDeps.add( id );
		}
	}
	const target = path.join( outDir, `${ name }.js` );
	fs.mkdirSync( path.dirname( target ), { recursive: true } );
	fs.writeFileSync( target, chunk.code );
	if ( styles.length && css ) {
		// A script module that imports styles gets a stylesheet next to it.
		const content = await css.join(
			styles.map( ( id ) => css.mark( id ) )
		);
		fs.writeFileSync( target.replace( /\.js$/, '.css' ), content );
		fs.writeFileSync(
			target.replace( /\.js$/, '-rtl.css' ),
			css.rtl( content )
		);
	}
	return [
		`${ name }.js`,
		{
			dependencies: [
				...[ ...staticDeps ].sort(),
				...[ ...dynamicDeps ]
					.sort()
					.map( ( id ) => ( { id, import: 'dynamic' } ) ),
			],
			version: createHash( 'sha1' )
				.update( chunk.code )
				.digest( 'hex' )
				.slice( 0, 20 ),
			type: 'module',
		},
	];
}

// `watched`, when given, collects every source file the modules read, for watch mode.
export async function buildScriptModules( {
	outDir = OUT,
	production = true,
	css,
	watched,
} = {} ) {
	const started = performance.now();
	const records = await Promise.all(
		Object.entries( moduleEntries ).map( ( [ name, file ] ) =>
			buildModule( name, file, outDir, production, css, watched )
		)
	);
	// Webpack also lists the style entries of this config; PHP reads the
	// versions for the stylesheets.
	const { styleEntries, editorStyleEntries } = blocksRequire(
		'./bin/interactivity-entries.js'
	);
	for ( const name of Object.keys( {
		...styleEntries,
		...editorStyleEntries,
	} ) ) {
		const cssFile = path.join( outDir, `${ name }.css` );
		const content = fs.existsSync( cssFile )
			? fs.readFileSync( cssFile )
			: name;
		records.push( [
			`${ name }.js`,
			{
				dependencies: [],
				version: createHash( 'sha1' )
					.update( content )
					.digest( 'hex' )
					.slice( 0, 20 ),
				type: 'module',
			},
		] );
	}
	fs.writeFileSync(
		path.join( outDir, 'interactivity-blocks-frontend-assets.php' ),
		`<?php return ${ php( Object.fromEntries( records ) ) };\n`
	);
	return {
		name: 'interactivity',
		ms: Math.round( performance.now() - started ),
		files: Object.keys( moduleEntries ).length + 1,
	};
}

export async function buildDependencyDetection( {
	outDir = OUT,
	watched,
} = {} ) {
	const started = performance.now();
	const bundle = await rolldown( {
		cwd: BLOCKS,
		input: {
			'dependency-detection': './assets/js/dependency-detection/index.ts',
		},
		platform: 'browser',
		tsconfig: false,
		transform: { target: TARGET },
		logLevel: 'warn',
	} );
	// Always minified: PHP prints this script inline.
	await bundle.write( {
		dir: outDir,
		format: 'iife',
		minify: true,
		codeSplitting: false,
		comments: { legal: false, annotation: false, jsdoc: false },
		entryFileNames: '[name].js',
	} );
	for ( const id of watched ? await bundle.watchFiles : [] ) {
		watched.add( id );
	}
	await bundle.close();
	return {
		name: 'dependency-detection',
		ms: Math.round( performance.now() - started ),
		files: 1,
	};
}

// Same rules as the CopyWebpackPlugin step of the main config.
export function copyBlockJson( { outDir = OUT } = {} ) {
	const started = performance.now();
	let count = 0;
	const walk = ( dir ) => {
		for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ) ) {
			const file = path.join( dir, entry.name );
			if ( entry.isDirectory() ) {
				walk( file );
			} else if ( entry.name === 'block.json' ) {
				const content = fs.readFileSync( file );
				const metadata = JSON.parse( content.toString() );
				const blockName = metadata.name.split( '/' ).at( 1 );
				const target =
					metadata.parent && ! genericBlocks[ blockName ]
						? path.join(
								outDir,
								'inner-blocks',
								blockName,
								'block.json'
							)
						: path.join( outDir, blockName, 'block.json' );
				fs.mkdirSync( path.dirname( target ), { recursive: true } );
				fs.writeFileSync( target, content );
				count++;
			}
		}
	};
	walk( path.join( BLOCKS, 'assets/js' ) );
	return {
		name: 'block-json',
		ms: Math.round( performance.now() - started ),
		files: count,
	};
}
