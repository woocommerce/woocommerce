import { build, context } from 'esbuild';
import { glob } from 'glob';
import { copyFile, mkdir, rm } from 'node:fs/promises';
import { dirname, join, relative } from 'node:path';

const watch = process.argv.includes( '--watch' );
const format = process.argv.includes( '--cjs' ) ? 'cjs' : 'esm';
const outdir = format === 'cjs' ? 'build' : 'build-module';

const entryPoints = await glob( 'src/**/*.{ts,tsx,js,jsx}', {
	ignore: [ '**/test/**', '**/stories/**', '**/*.d.ts' ],
} );

// block.json files are runtime-imported by src/blocks/**/index.ts. esbuild in
// transpile mode preserves the `import './block.json'` statements as-is, so
// the .json files must sit next to the emitted .js for those imports to
// resolve.
const blockJsonFiles = await glob( 'src/**/block.json' );
async function copyBlockJson() {
	for ( const src of blockJsonFiles ) {
		const dest = join( outdir, relative( 'src', src ) );
		await mkdir( dirname( dest ), { recursive: true } );
		await copyFile( src, dest );
	}
}

const options = {
	entryPoints,
	outdir,
	outbase: 'src',
	bundle: false,
	format,
	platform: 'neutral',
	target: 'esnext',
	loader: { '.js': 'jsx', '.jsx': 'jsx', '.ts': 'ts', '.tsx': 'tsx' },
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
	await copyBlockJson();
} else {
	await build( options );
	await copyBlockJson();
}
