// Watch mode, development builds. Prints one WATCH_END line per finished build.
// Usage: node watch.mjs admin|admin-app|blocks <outDir>
import { watchClassic } from './lib/classic.mjs';
import { adminConfigs } from './configs/admin.mjs';
import { classicConfigs, unifiedMainConfig } from './configs/blocks.mjs';
import { buildBlocksStyles } from './configs/blocks-styles.mjs';

const [ what, outDir ] = process.argv.slice( 2 );
let configs;
if ( what === 'blocks' ) {
	configs = [ ...classicConfigs( outDir ), unifiedMainConfig( outDir ) ].map( ( c ) => ( {
		...c,
		mode: 'development',
	} ) );
} else {
	configs = adminConfigs( { outDir, production: false } );
	if ( what === 'admin-app' ) {
		configs = configs.slice( 0, 1 );
	}
}
const start = ( config ) => {
	watchClassic( config, ( event, state ) => {
		if ( event.code === 'BUNDLE_END' ) {
			console.log( `WATCH_END ${ config.name } ${ event.duration } link=${ state.linkMs }` );
			event.result?.close();
		} else if ( event.code === 'ERROR' ) {
			console.log( `WATCH_ERROR ${ config.name } ${ String( event.error ).slice( 0, 300 ) }` );
		}
	} );
	return { name: config.name };
};
configs.forEach( start );
if ( what === 'blocks' ) {
	// The two style configs. The interactivity script modules are not watched yet.
	await buildBlocksStyles( { outDir, production: false, run: start } );
}
