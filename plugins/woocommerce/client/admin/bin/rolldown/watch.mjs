// Watch mode for the admin build: development builds with source maps.
// Usage: node bin/rolldown/watch.mjs [--out=<dir>]
import { watchAndLog } from '@woocommerce/rolldown-build/classic';
import { adminConfigs, OUT } from './config.mjs';

const out = process.argv.find( ( a ) => a.startsWith( '--out=' ) )?.slice( 6 ) || OUT;
adminConfigs( { outDir: out, production: false } ).forEach( watchAndLog );
