module.exports = {
	templatesPath: __dirname,
	defaultValues: {
		npmDependencies: [ '@wordpress/hooks', '@wordpress/i18n' ],
		npmDevDependencies: [
			'@woocommerce/dependency-extraction-webpack-plugin',
			'@woocommerce/eslint-plugin',
			'@wordpress/prettier-config',
			'@wordpress/scripts',
		],
		wpEnv: true,
		license: 'GPL-3.0+',
	},
};
