const { join } = require( 'path' );

const defaultDependencies = [
	'@wordpress/hooks',
	'@wordpress/i18n',
	'@woocommerce/components',
];
const defaultDevDependencies = [
	'@woocommerce/dependency-extraction-webpack-plugin',
	'@woocommerce/eslint-plugin',
	'@wordpress/prettier-config',
	'@wordpress/scripts',
];

module.exports = {
	templatesPath: join( __dirname, 'variants/default' ),
	defaultValues: {
		npmDependencies: defaultDependencies,
		npmDevDependencies: defaultDevDependencies,
		namespace: 'extension',
		license: 'GPL-3.0+',
		customScripts: {
			postinstall: 'rm block.json && composer install',
		},
	},
	variants: {
		'add-report': {
			pluginTemplatesPath: join( __dirname, 'variants/add-report' ),
			blockTemplatesPath: null,
			customScripts: {
				postinstall: 'rm block.json && composer install',
			},
		},
		'add-task': {
			pluginTemplatesPath: join( __dirname, 'variants/add-task' ),
			blockTemplatesPath: null,
			customScripts: {
				postinstall: 'rm block.json && composer install',
			},
			npmDependencies: [
				...defaultDependencies,
				'@woocommerce/onboarding',
			],
		},
		'dashboard-section': {
			pluginTemplatesPath: join(
				__dirname,
				'variants/dashboard-section'
			),
			blockTemplatesPath: null,
			customScripts: {
				postinstall: 'rm block.json && composer install',
			},
		},
		'table-column': {
			pluginTemplatesPath: join( __dirname, 'variants/table-column' ),
			blockTemplatesPath: null,
			customScripts: {
				postinstall: 'rm block.json && composer install',
			},
		},
	},
};
