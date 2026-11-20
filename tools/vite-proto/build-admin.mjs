// Full admin build: client/admin's webpack config.
import { buildClassic } from './lib/classic.mjs';
import { writeBundleReport } from './lib/analyze.mjs';
import { disposeSass } from './lib/css.mjs';
import { adminConfigs, OUT } from './configs/admin.mjs';
const args = Object.fromEntries( process.argv.slice( 2 ).map( ( a ) => {
	const [ k, v = true ] = a.replace( /^--/, '' ).split( '=' );
	return [ k, v ];
} ) );
const started = performance.now();
const configs = adminConfigs( { outDir: args.out || OUT, sass: args.sass || 'sass', perEntry: args.single !== true, production: ! args.dev } );
const results = await Promise.all( configs.map( ( c ) => buildClassic( c ).then( ( { state, ...r } ) => r ) ) );
await disposeSass();
const report = writeBundleReport( args.out || OUT, 'WooCommerce admin bundle report' );
if ( report ) {
	console.error( `Bundle report: ${ report }` );
}
const css = configs[ 0 ].css_;
console.log( JSON.stringify( {
	tool: 'rolldown',
	ms: Math.round( performance.now() - started ),
	builds: results.length,
	files: results.reduce( ( n, r ) => n + r.files, 0 ),
	slowest: results.sort( ( a, b ) => b.ms - a.ms ).slice( 0, 3 ).map( ( r ) => [ r.name, r.ms ] ),
	css: { modules: css.stats.modules, sassMs: Math.round( css.stats.sassMs ), postcssMs: Math.round( css.stats.postcssMs ) },
} ) );
