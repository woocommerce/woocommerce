// CJS build entry for @woocommerce/components.
//
// Transpiles every source file under src/ (excluding test and stories trees)
// into build/ in the same shape tsc produces — no bundling, no resolution of
// non-code imports (scss/png/etc.), no type emission. Type declarations are
// still produced by the ESM tsc build via tsconfig.json (declaration: true).
//
// Flags mirror the compiler settings inherited from
// @woocommerce/internal-ts-config/tsconfig-cjs.json: target esnext, CommonJS
// output, classic React JSX with `createElement`/`Fragment`.
//
// Usage:
//   node tools/esbuild-cjs.mjs           # one-shot build
//   node tools/esbuild-cjs.mjs --watch   # incremental rebuild

import { readdirSync, statSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { build, context } from 'esbuild';

const __dirname = dirname( fileURLToPath( import.meta.url ) );
const pkgRoot = dirname( __dirname );
const srcDir = join( pkgRoot, 'src' );
const outDir = join( pkgRoot, 'build' );

const EXCLUDED_DIRS = new Set( [ 'test', 'stories' ] );
// Match TS/TSX/JS/JSX, but exclude declaration files (`.d.ts`).
const SOURCE_RE = /(?<!\.d)\.(tsx?|jsx?)$/;

function collectEntryPoints( dir ) {
	const out = [];
	for ( const name of readdirSync( dir ) ) {
		const full = join( dir, name );
		const stat = statSync( full );
		if ( stat.isDirectory() ) {
			if ( EXCLUDED_DIRS.has( name ) ) continue;
			out.push( ...collectEntryPoints( full ) );
		} else if ( SOURCE_RE.test( name ) ) {
			out.push( full );
		}
	}
	return out;
}

const entryPoints = collectEntryPoints( srcDir );

const options = {
	entryPoints,
	outdir: outDir,
	outbase: srcDir,
	format: 'cjs',
	target: 'esnext',
	jsx: 'transform',
	jsxFactory: 'createElement',
	jsxFragment: 'Fragment',
	loader: { '.js': 'jsx' },
	logLevel: 'info',
};

if ( process.argv.includes( '--watch' ) ) {
	const ctx = await context( options );
	await ctx.watch();
} else {
	await build( options );
}
