const webpackOverride = require( '../webpack.config' );

module.exports = {
	core: {
		builder: 'webpack5',
	},
	stories: [
		// WooCommerce Admin / @woocommerce/components components
		'../../../packages/js/components/src/**/stories/*.@(js|tsx)',
		// WooCommerce Admin / @woocommerce/experimental components
		'../../../packages/js/experimental/src/**/stories/*.@(js|tsx)',
		// WooCommerce Admin / @woocommerce/onboarding components
		'../../../packages/js/onboarding/src/**/stories/*.@(js|tsx)',
		'../../../plugins/woocommerce-admin/client/**/stories/*.@(js|tsx)',
	],
	addons: [
		'@storybook/addon-docs',
		'@storybook/addon-controls',
		// This package has been deprecated, in favor of @storybook/addon-controls
		// However, it is still needed for the <Timeline /> story because changing the values with @storybook/addon-controls makes it crash. It seems that we cannot have jsx elements in props.
		'@storybook/addon-knobs',
		'@storybook/addon-viewport',
		'@storybook/addon-a11y',
		'@storybook/addon-actions',
		'@storybook/addon-links',
	],

	typescript: {
		reactDocgen: 'react-docgen-typescript',
	},

	staticDirs: [
		{
			from: '../../../plugins/woocommerce-admin/client',
			to: 'main/plugins/woocommerce-admin/client',
		},
	],

	webpackFinal: webpackOverride,

	previewHead: ( head ) => `
		${ head }

		${
			process.env.USE_RTL_STYLE === 'true'
				? `
			<link href="experimental-css/style-rtl.css" rel="stylesheet" />
			<link href="component-css/style-rtl.css" rel="stylesheet" />
			<link href="onboarding-css/style-rtl.css" rel="stylesheet" />
			<link href="app-css/style-rtl.css" rel="stylesheet" />
			`
				: `
			<link href="component-css/style.css" rel="stylesheet" />
			<link href="experimental-css/style.css" rel="stylesheet" />
			<link href="onboarding-css/style.css" rel="stylesheet" />
			<link href="app-css/style.css" rel="stylesheet" />
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
	previewBody: ( body ) => `
	<div id="wpwrap">
		<div class="woocommerce-layout woocommerce-admin-page">
			${ body }

	`,
};
