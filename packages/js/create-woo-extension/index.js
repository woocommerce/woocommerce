const { join } = require( 'path' );

const defaultDependencies = [
	'@wordpress/hooks',
	'@wordpress/i18n',
	'@woocommerce/components',
];
/**
 * `npmDevDependencies` cannot carry a version: create-block resolves each entry
 * with npm-package-arg and writes `saveSpec || 'latest'`, and `saveSpec` is null
 * for registry specs. Pins therefore have to go through `customPackageJSON`,
 * which is spread over the generated `devDependencies`.
 */
const defaultDevDependencies = {
	'@woocommerce/dependency-extraction-webpack-plugin': 'latest',
	/* v4 is the first Flat Config release; the scaffolded eslint.config.mjs requires it. */
	'@woocommerce/eslint-plugin': '^4.0.0',
	'@wordpress/prettier-config': 'latest',
	'@wordpress/scripts': 'latest',
	/**
	 * `@wordpress/prettier-config` peers `prettier: >=3`, which npm satisfies with
	 * plain prettier and then dedupes over the wp-prettier alias that
	 * `@wordpress/scripts` asks for. `eslint-plugin-prettier` resolves that hoisted
	 * copy, so `prettier/prettier` reports WordPress style as errors. Declaring the
	 * alias directly keeps wp-prettier at the top of the tree. Pinned exactly to
	 * match `@woocommerce/eslint-plugin`, so both resolve to a single copy.
	 */
	prettier: 'npm:wp-prettier@3.0.3',
};

module.exports = {
	pluginTemplatesPath: join( __dirname, 'variants', 'default' ),
	blockTemplatesPath: join( __dirname, 'variants', 'default', 'src' ),
	defaultValues: {
		npmDependencies: defaultDependencies,
		npmDevDependencies: Object.keys( defaultDevDependencies ),
		customPackageJSON: { devDependencies: defaultDevDependencies },
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
