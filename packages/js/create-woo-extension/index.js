const { join } = require( 'path' );

module.exports = {
	templatesPath: join( __dirname, 'variants/default' ),
	defaultValues: {
		npmDependencies: [
			'@wordpress/hooks',
			'@wordpress/i18n',
			'@woocommerce/components',
		],
		npmDevDependencies: [
			'@woocommerce/dependency-extraction-webpack-plugin',
			'@woocommerce/eslint-plugin',
			'@wordpress/prettier-config',
			'@wordpress/scripts',
		],
		namespace: 'extension',
		license: 'GPL-3.0+',
		customScripts: {
			postinstall: 'rm block.json && composer install',
		},
	},
	variants: {},
};
