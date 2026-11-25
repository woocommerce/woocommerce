// Full blocks build. Usage: node bin/rolldown/build.mjs [--dev] [--out=<dir>] [--only=<names>]
/* eslint-disable no-console -- command line script */
import { buildClassic } from '@woocommerce/rolldown-build/classic';
import { writeBundleReport } from '@woocommerce/rolldown-build/analyze';
import { execFileSync } from 'node:child_process';
import { disposeSass, createCss } from '@woocommerce/rolldown-build/css';
import { BLOCKS } from '@woocommerce/rolldown-build/shared';
import { classicConfigs, unifiedMainConfig, OUT } from './config.mjs';
import { buildBlocksStyles } from './styles.mjs';
import {
	buildScriptModules,
	buildDependencyDetection,
	copyBlockJson,
} from './modules.mjs';

const args = Object.fromEntries(
	process.argv.slice( 2 ).map( ( a ) => {
		const [ k, v = true ] = a.replace( /^--/, '' ).split( '=' );
		return [ k, v ];
	} )
);
const started = performance.now();
const outDir = args.out || OUT;
const only = args.only ? String( args.only ).split( ',' ) : null;
const want = ( name ) => ! only || only.includes( name );
// --dev or NODE_ENV=development: a development build with source maps.
const production = ! args.dev && process.env.NODE_ENV !== 'development';
const css = createCss( 'blocks', {
	production,
	minifier: args.minifier || 'cssnano',
	sass: args.sass || 'sass',
} );
const jobs = [
	...[ ...classicConfigs( outDir ), unifiedMainConfig( outDir ) ]
		.filter( ( c ) => want( c.name ) )
		.map( ( c ) => ( production ? c : { ...c, mode: 'development' } ) )
		.map( ( c ) => buildClassic( c ).then( ( { state, ...r } ) => r ) ),
	want( 'styles' ) &&
		buildBlocksStyles( { outDir, css, production } ).then( async ( r ) => {
			// The module asset file hashes the stylesheets, so it goes after them.
			const modules = want( 'interactivity' )
				? await buildScriptModules( { outDir, css, production } )
				: null;
			return { name: 'styles', ...r, modules };
		} ),
	want( 'dependency-detection' ) && buildDependencyDetection( { outDir } ),
	want( 'block-json' ) &&
		Promise.resolve( copyBlockJson( { outDir } ) ).then( ( r ) => {
			// blocks-json.php, the manifest PHP reads to register the blocks.
			const t = performance.now();
			execFileSync(
				'pnpm',
				[
					'exec',
					'wp-scripts',
					'build-blocks-manifest',
					`--input=${ outDir }`,
					`--output=${ outDir }/blocks-json.php`,
				],
				{ cwd: BLOCKS, stdio: 'ignore' }
			);
			return { ...r, manifestMs: Math.round( performance.now() - t ) };
		} ),
].filter( Boolean );
const results = await Promise.all( jobs );
await disposeSass();
const report = writeBundleReport( outDir, 'WooCommerce blocks bundle report' );
if ( report ) {
	console.error( `Bundle report: ${ report }` );
}
console.log(
	JSON.stringify( {
		tool: 'rolldown',
		ms: Math.round( performance.now() - started ),
		results,
	} )
);
