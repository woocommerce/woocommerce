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
	framework: {
		name: '@storybook/react-webpack5',
		options: {},
	},
};
