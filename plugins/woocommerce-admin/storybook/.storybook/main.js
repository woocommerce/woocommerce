const webpackOverride = require( '../webpack.config' );

module.exports = {
	core: {
		builder: 'webpack5',
	},
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

	previewHead: ( head ) => `
		${ head }

		${
			process.env.USE_RTL_STYLE === 'true'
				? `
			<link href="experimental-css/style-rtl.css" rel="stylesheet" />
			<link href="component-css/style-rtl.css" rel="stylesheet" />
			`
				: `
			<link href="component-css/style.css" rel="stylesheet" />
			<link href="experimental-css/style.css" rel="stylesheet" />
			`
		}

		<style>
			/* Use system font, consistent with WordPress core (wp-admin) */
			body {
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
					Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
			}
		</style>
	`,
};
