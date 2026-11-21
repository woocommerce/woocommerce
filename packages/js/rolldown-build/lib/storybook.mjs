/**
 * Vite settings both Storybooks share, so they compile code and styles the
 * way the Rolldown build does.
 */
import path from 'node:path';
import { transform } from 'rolldown/utils';
import { viteCss } from './css.mjs';

// Vite only compiles JSX in .jsx and .tsx files. WooCommerce has JSX in .js
// files too, which the build reads as JSX (`moduleTypes: { '.js': 'jsx' }`).
function jsxInJs() {
	return {
		name: 'woocommerce:jsx-in-js',
		enforce: 'pre',
		async transform( code, id ) {
			if ( ! /\.js$/.test( id ) || /[\\/]node_modules[\\/]/.test( id ) ) {
				return null;
			}
			const result = await transform( id, code, {
				lang: 'jsx',
				jsx: { runtime: 'automatic' },
				sourcemap: true,
			} );
			if ( result.errors?.length ) {
				this.error( result.errors[ 0 ].message );
			}
			return { code: result.code, map: result.map };
		},
	};
}

// Storybook's webpack builder searched the NODE_PATH folders, where pnpm puts
// its hoisted packages. Blocks stories import `@wordpress/*` packages that the
// blocks package never declares, since the build keeps them external.
function nodePathFallback() {
	const dirs = ( process.env.NODE_PATH || '' )
		.split( path.delimiter )
		.filter( Boolean );
	return {
		name: 'woocommerce:node-path-fallback',
		async resolveId( source, importer, options ) {
			if ( ! dirs.length || /^[./\0]|^[a-z]+:/i.test( source ) ) {
				return null;
			}
			const found = await this.resolve( source, importer, {
				...options,
				skipSelf: true,
			} );
			if ( found ) {
				return found;
			}
			for ( const dir of dirs ) {
				// Bare imports resolve from `<dir>` for an importer next to it.
				const resolved = await this.resolve(
					source,
					path.join( dir, '..', 'storybook-fallback.js' ),
					{ ...options, skipSelf: true }
				);
				if ( resolved ) {
					return resolved;
				}
			}
			return null;
		},
	};
}

/**
 * @param {'blocks'|'admin'} kind Which style setup to use.
 * @param {{ rtl?: boolean }} options
 */
export function storybookVite( kind, { rtl = false } = {} ) {
	return {
		plugins: [ jsxInJs(), nodePathFallback() ],
		css: viteCss( kind, { rtl } ),
		// The repo tsconfigs say `"jsx": "preserve"`, which Vite would follow.
		oxc: { jsx: { runtime: 'automatic' } },
		// The webpack Storybooks did not minify CSS, and lightningcss rejects
		// some selectors WordPress styles use.
		build: {
			cssMinify: false,
			// Some index files re-export TypeScript types without `type`, as
			// in the build.
			rolldownOptions: {
				shimMissingExports: true,
				// `"use client"` lines in dependencies; the build hides them too.
				onLog( level, log, handler ) {
					if ( log.code !== 'MODULE_LEVEL_DIRECTIVE' ) {
						handler( level, log );
					}
				},
			},
		},
		define: {
			__i18n_text_domain__: '"woocommerce"',
			// Webpack maps Node's `global` to the global object for web builds.
			global: 'globalThis',
		},
	};
}
