// Full blocks build: every webpack config of client/blocks.
import { buildClassic } from './lib/classic.mjs';
import { writeBundleReport } from './lib/analyze.mjs';
import { execFileSync } from 'node:child_process';
import { disposeSass, createCss } from './lib/css.mjs';
import { BLOCKS } from './lib/shared.mjs';
import { classicConfigs, unifiedMainConfig, OUT } from './configs/blocks.mjs';
import { buildBlocksStyles } from './configs/blocks-styles.mjs';
import { buildScriptModules, buildDependencyDetection, copyBlockJson } from './configs/blocks-modules.mjs';

const args = Object.fromEntries( process.argv.slice( 2 ).map( ( a ) => {
	const [ k, v = true ] = a.replace( /^--/, '' ).split( '=' );
	return [ k, v ];
} ) );
const started = performance.now();
const outDir = args.out || OUT;
const only = args.only ? String( args.only ).split( ',' ) : null;
const want = ( name ) => ! only || only.includes( name );
// --dev: a development build, like webpack with NODE_ENV=development.
const production = ! args.dev;
const css = createCss( 'blocks', { production, minifier: args.minifier || 'cssnano', sass: args.sass || 'sass' } );
const jobs = [
	...[ ...classicConfigs( outDir ), unifiedMainConfig( outDir ) ]
		.filter( ( c ) => want( c.name ) )
		.map( ( c ) => ( production ? c : { ...c, mode: 'development' } ) )
		.map( ( c ) => buildClassic( c ).then( ( { state, ...r } ) => r ) ),
	want( 'styles' ) && buildBlocksStyles( { outDir, css, production } )
		.then( async ( r ) => {
			// The module asset file hashes the stylesheets, so it goes after them.
			const modules = want( 'interactivity' ) ? await buildScriptModules( { outDir, css, production } ) : null;
			return { name: 'styles', ...r, modules };
		} ),
	want( 'dependency-detection' ) && buildDependencyDetection( { outDir } ),
	want( 'block-json' ) && Promise.resolve( copyBlockJson( { outDir } ) ).then( ( r ) => {
		// The same manifest step trunk runs after webpack.
		const t = performance.now();
		execFileSync( 'pnpm', [ 'exec', 'wp-scripts', 'build-blocks-manifest', `--input=${ outDir }`, `--output=${ outDir }/blocks-json.php` ], { cwd: BLOCKS, stdio: 'ignore' } );
		return { ...r, manifestMs: Math.round( performance.now() - t ) };
	} ),
].filter( Boolean );
const results = await Promise.all( jobs );
await disposeSass();
const report = writeBundleReport( outDir, 'WooCommerce blocks bundle report' );
if ( report ) {
	console.error( `Bundle report: ${ report }` );
}
console.log( JSON.stringify( { tool: 'rolldown', ms: Math.round( performance.now() - started ), results } ) );
