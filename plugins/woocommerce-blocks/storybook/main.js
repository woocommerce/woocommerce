/**
 * External dependencies
 */
const path = require( 'path' );

module.exports = {
	stories: [
		// WooCommerce Blocks stuff (anywhere in repo!)
		'../assets/js/**/stories/*.stories.@(js|jsx|ts|tsx)',
		'../packages/**/stories/*.stories.@(js|jsx|ts|tsx)',
		'../assets/js/**/*.mdx',
		'../packages/**/*.mdx',
	],
	addons: [
		'@storybook/addon-essentials',
		'@storybook/addon-docs',
		'@storybook/addon-a11y',
		'@storybook/addon-links',
		'storybook-addon-react-docgen',
		'@storybook/addon-styling-webpack',
	],
	typescript: {
		reactDocgen: 'react-docgen-typescript-plugin',
	},
	docs: {
		autodocs: true,
		defaultName: 'Docs',
	},
	features: {
		babelModeV7: true,
		emotionAlias: false,
	},
	core: {
		builder: 'webpack5',
	},
	// webpackFinal field was added in following PR: https://github.com/woocommerce/woocommerce-blocks/pull/7514
	// This fixes "storybook build issue" related to framer-motion library.
	// Solution is from this comment: https://github.com/storybookjs/storybook/issues/16690#issuecomment-971579785
	webpackFinal: async ( config ) => {
		config.module.rules.push( {
			test: /\.mjs$/,
			include: /node_modules/,
			type: 'javascript/auto',
		} );
		config.externals = [ 'react-dom/client' ];
		// https://github.com/storybookjs/storybook/discussions/22650#discussioncomment-6414161
		config.resolve.alias = {
			...config.resolve.alias,
			react: path.resolve( __dirname, '../node_modules/react' ),
			'react-dom': path.resolve( __dirname, '../node_modules/react-dom' ),
		};
		return config;
	},
	framework: {
		name: '@storybook/react-webpack5',
		options: {},
	},
};
