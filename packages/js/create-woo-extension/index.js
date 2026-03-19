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
	pluginTemplatesPath: join( __dirname, 'variants', 'default' ),
	blockTemplatesPath: join( __dirname, 'variants', 'default', 'src' ),
	defaultValues: {
		npmDependencies: defaultDependencies,
		npmDevDependencies: defaultDevDependencies,
		namespace: 'extension',
		license: 'GPL-3.0+',
		customScripts: {
			postinstall: 'rm -f src/block.json && composer install',
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
			pluginTemplatesPath: join( __dirname, 'variants', 'add-report' ),
			blockTemplatesPath: join(
				__dirname,
				'variants',
				'add-report',
				'src'
			),
		},
		'add-task': {
			pluginTemplatesPath: join( __dirname, 'variants', 'add-task' ),
			blockTemplatesPath: join(
				__dirname,
				'variants',
				'add-task',
				'src'
			),
			npmDependencies: [
				...defaultDependencies,
				'@woocommerce/onboarding',
			],
		},
		'dashboard-section': {
			pluginTemplatesPath: join(
				__dirname,
				'variants',
				'dashboard-section'
			),
			blockTemplatesPath: join(
				__dirname,
				'variants',
				'dashboard-section',
				'src'
			),
		},
		'table-column': {
			pluginTemplatesPath: join( __dirname, 'variants', 'table-column' ),
			blockTemplatesPath: join(
				__dirname,
				'variants',
				'table-column',
				'src'
			),
		},
		'sql-modification': {
			pluginTemplatesPath: join(
				__dirname,
				'variants',
				'sql-modification'
			),
			blockTemplatesPath: join(
				__dirname,
				'variants',
				'sql-modification',
				'src'
			),
		},
	},
};
