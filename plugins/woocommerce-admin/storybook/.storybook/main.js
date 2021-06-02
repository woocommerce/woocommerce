const webpackOverride = require( '../webpack.config' );

module.exports = {
	stories: [
		// WooCommerce Admin / @woocommerce/components components
		'../../packages/components/src/**/stories/*.@(js|tsx)',
		// WooCommerce Admin / @woocommerce/experimental components
		'../../packages/experimental/src/**/stories/*.@(js|tsx)',
		'../../client/**/stories/*.js',
	],
	addons: [
		'@storybook/addon-docs',
		'@storybook/addon-knobs',
		'@storybook/addon-viewport',
		'@storybook/addon-a11y',
		'@storybook/addon-actions',
		'@storybook/addon-links',
	],

	typescript: {
		reactDocgen: 'react-docgen-typescript',
	},

	webpackFinal: webpackOverride,
};
