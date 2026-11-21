/**
 * External dependencies
 */
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { mergeConfig, defaultClientConditions } from 'vite';
import { storybookVite } from '@woocommerce/rolldown-build/storybook';

const require = createRequire( import.meta.url );
const BLOCKS = path.resolve(
	path.dirname( fileURLToPath( import.meta.url ) ),
	'..'
);
const tsConfig = require( '../tsconfig.base.json' );

// The tsconfig path aliases, as the build uses them. `@wordpress/*` paths
// point at type folders, and `.d.ts` mappings only exist for `tsc`, so skip
// both and let those resolve from node_modules.
const tsconfigAliases = Object.entries( tsConfig.compilerOptions.paths )
	.filter(
		( [ key, [ target ] ] ) =>
			! key.startsWith( '@wordpress' ) && ! target.endsWith( '.d.ts' )
	)
	.map( ( [ key, [ target ] ] ) => ( {
		find: key.replace( '/*', '' ),
		replacement: path.resolve( BLOCKS, target.replace( '/*', '' ) ),
	} ) );

const exact = ( name ) => new RegExp( `^${ name.replace( /\//g, '\\/' ) }$` );

/** @type {import('@storybook/react-vite').StorybookConfig} */
const storybookConfig = {
	stories: [
		// WooCommerce Blocks stuff (anywhere in repo!)
		'../assets/js/**/stories/*.stories.@(js|jsx|ts|tsx)',
		'../packages/**/stories/*.stories.@(js|jsx|ts|tsx)',
		'../assets/js/**/*.mdx',
		'../packages/**/*.mdx',
	],
	addons: [
		'@storybook/addon-docs',
		'@storybook/addon-a11y',
		'@storybook/addon-links',
	],
	typescript: {
		reactDocgen: 'react-docgen-typescript',
	},
	docs: {
		defaultName: 'Docs',
	},
	viteFinal: ( config ) =>
		mergeConfig( config, {
			...storybookVite( 'blocks' ),
			resolve: {
				alias: [
					{
						find: exact( '@woocommerce/block-settings' ),
						replacement:
							require.resolve( './__mocks__/woocommerce-block-settings.js' ),
					},
					{
						find: exact( '@woocommerce/base-hooks' ),
						replacement:
							require.resolve( './__mocks__/woocommerce-base-hooks.js' ),
					},
					{
						find: 'wordpress-components',
						replacement: path.dirname(
							require.resolve(
								'wordpress-components/package.json',
								{ paths: [ BLOCKS ] }
							)
						),
					},
					...tsconfigAliases,
				],
				// Load `@woocommerce/*` packages from source, like the build.
				conditions: [ 'wc-source', ...defaultClientConditions ],
				dedupe: [ 'react', 'react-dom' ],
			},
		} ),
	framework: {
		name: '@storybook/react-vite',
		options: {},
	},
};

export default storybookConfig;
