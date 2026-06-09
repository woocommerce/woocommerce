/**
 * External dependencies
 */
import type { BuildOptions as EsbuildOptions } from 'esbuild';

export interface BuildOptions {
	entryPoints: string | string[];
	ignore?: string[];
	format?: 'esmodules' | 'commonjs';
	assets?: string[];
	esbuild?: EsbuildOptions;
}

export const DEFAULT_IGNORE: readonly string[] = [
	'**/test/**',
	'**/stories/**',
	'**/*.test.{ts,tsx,js,jsx}',
	'**/*.d.ts',
];

export function prepareEsbuildOptions(
	format: 'esmodules' | 'commonjs',
	entryPoints: string[],
	overrides: EsbuildOptions = {}
): EsbuildOptions {
	return {
		entryPoints,
		outdir: format === 'commonjs' ? 'build' : 'build-module',
		outbase: 'src',
		bundle: false,
		format: format === 'commonjs' ? 'cjs' : 'esm',
		platform: 'neutral',
		target: 'esnext',
		loader: { '.js': 'jsx', '.jsx': 'jsx', '.ts': 'ts', '.tsx': 'tsx' },
		jsx: 'transform',
		jsxFactory: 'createElement',
		jsxFragment: 'Fragment',
		logLevel: 'warning',
		sourcemap: false,
		...overrides,
	};
}
