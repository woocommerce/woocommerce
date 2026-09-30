/**
 * External dependencies
 */
import { createRequire } from 'node:module';
import path from 'node:path';

const require = createRequire( import.meta.url );

/** @type {import('@storybook/react-webpack5').StorybookConfig} */
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
	// webpackFinal field was added in following PR: https://github.com/woocommerce/woocommerce-blocks/pull/7514
	// This fixes "storybook build issue" related to framer-motion library.
	// Solution is from this comment: https://github.com/storybookjs/storybook/issues/16690#issuecomment-971579785
	webpackFinal: async ( config ) => {
		config.module ??= {};
		config.module.rules ??= [];
		config.module.rules.push( {
			test: /\.mjs$/,
			include: /node_modules/,
			type: 'javascript/auto',
		} );
		// https://github.com/storybookjs/storybook/discussions/22650#discussioncomment-6414161
		config.resolve ??= {};
		config.resolve.alias = {
			...config.resolve.alias,
			'react/jsx-runtime': require.resolve( 'react/jsx-runtime' ),
			react: path.dirname( require.resolve( 'react/package.json' ) ),
			'react-dom': path.dirname(
				require.resolve( 'react-dom/package.json' )
			),
		};
		return config;
	},
	framework: {
		name: '@storybook/react-webpack5',
		options: {},
	},
};

export default storybookConfig;
