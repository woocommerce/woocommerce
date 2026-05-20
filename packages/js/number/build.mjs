import { build, context } from 'esbuild';
import { glob } from 'glob';
import { rm } from 'node:fs/promises';

const watch = process.argv.includes( '--watch' );
const format = process.argv.includes( '--cjs' ) ? 'cjs' : 'esm';
const outdir = format === 'cjs' ? 'build' : 'build-module';

const entryPoints = await glob( 'src/**/*.{ts,tsx,js,jsx}', {
	ignore: [ '**/test/**', '**/stories/**', '**/*.d.ts' ],
} );

const options = {
	entryPoints,
	outdir,
	outbase: 'src',
	bundle: false,
	format,
	platform: 'neutral',
	target: 'esnext',
	loader: { '.js': 'jsx', '.jsx': 'jsx', '.ts': 'tsx', '.tsx': 'tsx' },
	jsx: 'transform',
	jsxFactory: 'createElement',
	jsxFragment: 'Fragment',
	logLevel: 'info',
	sourcemap: false,
};

await rm( outdir, { recursive: true, force: true } );

if ( watch ) {
	const ctx = await context( options );
	await ctx.watch();
} else {
	await build( options );
}
