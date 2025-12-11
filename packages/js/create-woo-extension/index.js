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
			postinstall: 'rm -f block.json && composer install',
		},
		transformer: ( view ) => {
			return {
				...view,
				namespaceConstantCase: view.namespace
					.toUpperCase()
					.replace( /-/g, '_' ),
				slugConstantCase: view.slug.toUpperCase().replace( /-/g, '_' ),
			};
		},
	},
	variants: {
		'add-report': {
			pluginTemplatesPath: join( __dirname, 'variants/add-report' ),
			blockTemplatesPath: null,
		},
		'add-task': {
			pluginTemplatesPath: join( __dirname, 'variants/add-task' ),
			blockTemplatesPath: null,
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
		},
		'table-column': {
			pluginTemplatesPath: join( __dirname, 'variants/table-column' ),
			blockTemplatesPath: null,
		},
		'sql-modification': {
			pluginTemplatesPath: join( __dirname, 'variants/sql-modification' ),
			blockTemplatesPath: null,
		},
	},
};
