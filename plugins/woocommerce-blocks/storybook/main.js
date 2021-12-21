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
};
