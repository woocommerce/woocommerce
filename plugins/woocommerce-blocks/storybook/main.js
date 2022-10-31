module.exports = {
	stories: [
		// WooCommerce Blocks stuff (anywhere in repo!)
		'../assets/js/**/stories/*.@(js|jsx|ts|tsx)',
		'../packages/**/stories/*.@(js|jsx|ts|tsx)',
	],
	addons: [
		'@storybook/addon-essentials',
		'@storybook/addon-a11y',
		'@storybook/addon-links',
		'storybook-addon-react-docgen',
	],
	features: {
		babelModeV7: true,
		emotionAlias: false,
	},
	// webpackFinal field was added in following PR: https://github.com/woocommerce/woocommerce-blocks/pull/7514
	// This fixes "storybook build issue" related to framer-motion library.
	// Solution is from this commment: https://github.com/storybookjs/storybook/issues/16690#issuecomment-971579785
	webpackFinal: async ( config ) => {
		config.module.rules.push( {
			test: /\.mjs$/,
			include: /node_modules/,
			type: 'javascript/auto',
		} );
		return config;
	},
};
