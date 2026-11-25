// Watch mode for the blocks build: development builds with source maps.
/* eslint-disable no-console -- command line script */
// Usage: node bin/rolldown/watch.mjs [--out=<dir>]
import fs from 'node:fs';
import path from 'node:path';
import { watchAndLog } from '@woocommerce/rolldown-build/classic';
import { createCss } from '@woocommerce/rolldown-build/css';
import { BLOCKS } from '@woocommerce/rolldown-build/shared';
import { classicConfigs, unifiedMainConfig, OUT } from './config.mjs';
import { buildBlocksStyles } from './styles.mjs';
import {
	buildScriptModules,
	buildDependencyDetection,
	copyBlockJson,
} from './modules.mjs';

const outDir =
	process.argv.find( ( a ) => a.startsWith( '--out=' ) )?.slice( 6 ) || OUT;

[ ...classicConfigs( outDir ), unifiedMainConfig( outDir ) ]
	.map( ( config ) => ( { ...config, mode: 'development' } ) )
	.forEach( watchAndLog );

const css = createCss( 'blocks', { production: false } );
await buildBlocksStyles( { outDir, css, production: false, run: watchAndLog } );

// Script modules, dependency detection and block.json are small one-shot
// builds: run them again when a file they read changes.
const steps = {
	interactivity: ( watched ) =>
		buildScriptModules( { outDir, css, production: false, watched } ),
	'dependency-detection': ( watched ) =>
		buildDependencyDetection( { outDir, watched } ),
	'block-json': () => copyBlockJson( { outDir } ),
};
const watchedBy = {};
const run = async ( name ) => {
	const started = performance.now();
	const watched = new Set();
	try {
		await steps[ name ]( watched );
		watchedBy[ name ] = watched;
		console.log(
			`Built ${ name } in ${ Math.round( performance.now() - started ) } ms`
		);
	} catch ( error ) {
		console.error( `Error in ${ name }: ${ error }` );
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
