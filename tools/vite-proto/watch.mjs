// Watch mode, development builds. Prints one WATCH_END line per finished build.
// Usage: node watch.mjs admin|admin-app|blocks <outDir>
import { watchClassic } from './lib/classic.mjs';
import { adminConfigs } from './configs/admin.mjs';
import { classicConfigs, unifiedMainConfig } from './configs/blocks.mjs';
import { buildBlocksStyles } from './configs/blocks-styles.mjs';
import { buildScriptModules, buildDependencyDetection, copyBlockJson } from './configs/blocks-modules.mjs';
import { createCss } from './lib/css.mjs';
import { BLOCKS } from './lib/shared.mjs';
import fs from 'node:fs';
import path from 'node:path';

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
	const css = createCss( 'blocks', { production: false } );
	// The two style configs.
	await buildBlocksStyles( { outDir, css, production: false, run: start } );
	// Script modules, dependency detection and block.json are small one-shot
	// builds: run them again when a file they read changes.
	const steps = {
		interactivity: ( watched ) => buildScriptModules( { outDir, css, production: false, watched } ),
		'dependency-detection': ( watched ) => buildDependencyDetection( { outDir, watched } ),
		'block-json': () => copyBlockJson( { outDir } ),
	};
	const watchedBy = {};
	const run = async ( name ) => {
		const started = performance.now();
		const watched = new Set();
		try {
			await steps[ name ]( watched );
			watchedBy[ name ] = watched;
			console.log( `WATCH_END ${ name } ${ Math.round( performance.now() - started ) }` );
		} catch ( error ) {
			console.log( `WATCH_ERROR ${ name } ${ String( error ).slice( 0, 300 ) }` );
		}
	};
	await Promise.all( Object.keys( steps ).map( run ) );
	const pending = new Set();
	let timer;
	fs.watch( path.join( BLOCKS, 'assets' ), { recursive: true }, ( _, file ) => {
		if ( ! file ) {
			return;
		}
		const full = path.join( BLOCKS, 'assets', file );
		if ( path.basename( full ) === 'block.json' ) {
			pending.add( 'block-json' );
		}
		for ( const [ name, watched ] of Object.entries( watchedBy ) ) {
			if ( watched.has( full ) ) {
				pending.add( name );
			}
		}
		clearTimeout( timer );
		timer = setTimeout( () => {
			const names = [ ...pending ];
			pending.clear();
			names.forEach( run );
		}, 50 );
	} );
}
