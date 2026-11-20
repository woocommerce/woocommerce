import path from 'node:path';
import { createRequire } from 'node:module';
import { BLOCKS, ASSETS, REPO, TARGET, externalsFor, paramCase } from '@woocommerce/rolldown-build/shared';

process.env.NODE_ENV = process.env.NODE_ENV || 'production';
const previousCwd = process.cwd();
// The entry lists use globs relative to the blocks package.
process.chdir( BLOCKS );
const blocksRequire = createRequire( path.join( BLOCKS, 'package.json' ) );
const helpers = blocksRequire( './bin/build-helpers.js' );
const { getEntryConfig } = blocksRequire( './bin/entries.js' );
process.chdir( previousCwd );

export const OUT = path.join( ASSETS, 'blocks' );
const alias = helpers.getAlias();
const externals = externalsFor( helpers.requestToExternal, helpers.requestToHandle );

const base = {
	root: REPO,
	cwd: BLOCKS,
	target: TARGET,
	alias,
	injectPolyfill: true,
	// The script configs skip styles; the styling config builds them.
	moduleTypes: { '.scss': 'empty', '.sass': 'empty' },
	publicPath: { global: '__wcBlocksPublicPath' },
	define: { __webpack_public_path__: 'globalThis.__wcBlocksPublicPath' },
	...externals,
};

const entriesOf = ( type, { out, library } ) =>
	Object.entries( getEntryConfig( type ) ).map( ( [ name, files ] ) => ( {
		name,
		files: [].concat( files ),
		out: out( name ),
		library: library ? library( name ) : null,
	} ) );

const NODE_MODULES = /[\\/]node_modules[\\/]/;

export const classicConfigs = ( outDir = OUT ) => [
	{
		...base,
		name: 'cart-checkout-frontend',
		registry: '__wcBlocksCartCheckout',
		outDir,
		extensions: [ '.js', '.ts', '.tsx' ],
		entries: entriesOf( 'cartAndCheckoutFrontend', {
			out: ( name ) =>
				name === 'blocksCheckout' || name === 'blocksComponents'
					? `${ paramCase( name ) }.js`
					: `${ name }-frontend.js`,
			library: ( name ) => [ 'wc', name ],
		} ),
		namedChunks: [
			{
				name: 'wc-cart-checkout-vendors',
				file: 'wc-cart-checkout-vendors-frontend.js',
				test: NODE_MODULES,
			},
			{
				name: 'wc-cart-checkout-base',
				file: 'wc-cart-checkout-base-frontend.js',
				test: /assets[\\/]js[\\/](settings|previews|base|utils|blocks[\\/]cart-checkout-shared|icons)|packages[\\/]public-api[\\/](block-data|blocks-checkout|blocks-components|settings)[\\/]/,
			},
		],
		addSplitDeps: true,
		lazyFile: ( key ) => `${ key }-frontend.js`,
	},
	{
		...base,
		name: 'core',
		registry: '__wcBlocksCore',
		outDir,
		extensions: [ '.js', '.ts', '.tsx' ],
		entries: entriesOf( 'core', {
			out: ( name ) => `${ paramCase( name ) }.js`,
			library: ( name ) => [ 'wc', name ],
		} ),
		lazyFile: ( key ) => `core-${ key }.js`,
	},
	{
		...base,
		name: 'main',
		registry: '__wcBlocksMain',
		outDir,
		extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		entries: entriesOf( 'main', {
			out: ( name ) => `${ name }.js`,
			library: ( name ) => [ 'wc', 'blocks', name ],
		} ),
		namedChunks: [
			{ name: 'wc-blocks-vendors', file: 'wc-blocks-vendors.js', test: NODE_MODULES },
		],
		lazyFile: ( key ) => `${ key }.js`,
	},
	{
		...base,
		name: 'frontend',
		registry: '__wcBlocksFrontend',
		outDir,
		extensions: [ '.js', '.ts', '.tsx' ],
		entries: entriesOf( 'frontend', {
			out: ( name ) => `${ name }-frontend.js`,
			library: ( name ) => [ 'wc', name ],
		} ),
		namedChunks: [
			{
				name: 'wc-blocks-frontend-vendors',
				file: 'wc-blocks-frontend-vendors-frontend.js',
				test: NODE_MODULES,
			},
		],
		addSplitDeps: true,
		lazyFile: ( key ) => `${ key }-frontend.js`,
	},
	{
		...base,
		name: 'extensions',
		registry: '__wcBlocksExtensions',
		outDir,
		extensions: [ '.js', '.ts', '.tsx' ],
		entries: entriesOf( 'extensions', { out: ( name ) => `${ name }.js` } ),
		lazyFile: ( key ) => `extensions-${ key }.js`,
	},
	{
		...base,
		name: 'payments',
		registry: '__wcBlocksPayments',
		outDir,
		extensions: [ '.js', '.ts', '.tsx' ],
		entries: entriesOf( 'payments', { out: ( name ) => `${ name }.js` } ),
		lazyFile: ( key ) => `payments-${ key }.js`,
	},
];

// Mirrors bin/webpack-config-block-editor-unified-assets.js.
const editorExternalPackages = [
	'@woocommerce/block-data',
	'@woocommerce/blocks-checkout',
	'@woocommerce/blocks-checkout-events',
	'@woocommerce/blocks-components',
	'@woocommerce/blocks-registry',
	'@woocommerce/data',
	'@woocommerce/entities',
	'@woocommerce/price-format',
	'@woocommerce/sanitize',
	'@woocommerce/settings',
	'@woocommerce/shared-context',
	'@woocommerce/shared-hocs',
	'@woocommerce/types',
];
const bundleWooPackage = ( request ) =>
	request.startsWith( '@woocommerce/' ) &&
	! editorExternalPackages.includes( request );
const unifiedExternals = externalsFor(
	( request ) =>
		bundleWooPackage( request ) ? false : helpers.requestToExternal( request ),
	( request ) =>
		bundleWooPackage( request ) ? false : helpers.requestToHandle( request )
);

export const unifiedMainConfig = ( outDir = OUT ) => ( {
	...base,
	...unifiedExternals,
	name: 'unified-main',
	registry: '__wcBlocksUnifiedMain',
	outDir,
	extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
	alias: {
		'@woocommerce/data': path.join( REPO, 'packages/js/data/src/index.ts' ),
		'@woocommerce/sanitize': path.join( REPO, 'packages/js/sanitize/src/index.ts' ),
		...alias,
	},
	entries: [
		{
			name: 'wc-block-library',
			files: Object.values( getEntryConfig( 'main' ) ).flat(),
			out: 'wc-block-library.js',
			library: null,
		},
	],
	// One file, like LimitChunkCountPlugin with maxChunks 1.
	single: true,
	lazyFile: ( key ) => `wc-block-library-${ key }.js`,
} );

export { alias, externals, getEntryConfig, helpers, blocksRequire, base };
