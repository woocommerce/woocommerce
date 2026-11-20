import { buildBlocksStyles } from './configs/blocks-styles.mjs';
import { disposeSass } from './lib/css.mjs';
const args = Object.fromEntries( process.argv.slice( 2 ).map( ( a ) => { const [ k, v = true ] = a.replace( /^--/, '' ).split( '=' ); return [ k, v ]; } ) );
const result = await buildBlocksStyles( { outDir: args.out, minifier: args.minifier || 'cssnano', sass: args.sass || 'sass' } );
console.log( JSON.stringify( result ) );
const t = performance.now();
await disposeSass();
console.error( 'dispose ms', Math.round( performance.now() - t ) );
