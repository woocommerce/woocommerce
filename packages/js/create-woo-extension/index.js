const variantIndex = process.argv.indexOf( '--variant' );
let templatesPath = __dirname;
if ( variantIndex > -1 && process.argv[ variantIndex + 1 ] ) {
	templatesPath = __dirname + '/examples/' + process.argv[ variantIndex + 1 ];
}
module.exports = {
	templatesPath,
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
	variants: {
		'add-task': {
			npmDependencies: [
				'@wordpress/hooks',
				'@wordpress/i18n',
				'@woocommerce/components',
				'@woocommerce/onboarding',
			],
		},
	},
};
