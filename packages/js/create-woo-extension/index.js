module.exports = {
	templatesPath: __dirname,
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
			'@wordpress/scripts@24.6.0',
		],
		namespace: 'extension',
		license: 'GPL-3.0+',
		customScripts: {
			postinstall: 'composer install',
		},
	},
};
