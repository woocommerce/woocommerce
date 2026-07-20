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
	/*
	 * Without this, `@wordpress/prettier-config`'s `prettier: >=3` peer hoists plain
	 * prettier over wp-prettier. Pinned to match `@woocommerce/eslint-plugin`.
	 */
	prettier: 'npm:wp-prettier@3.0.3',
};

/*
 * Stopgap: `@woocommerce/components` pulls `i18n-calypso@7.4.1` (via tour-kit),
 * which was published with a Yarn-only `patch:` spec that npm cannot parse, so
 * `npm install` fails before lint is ever reached. 7.4.0 is the newest release
 * without the bad spec; 8.x is out of the consumer's `^7.4.0` range. Remove once
 * the upstream `@automattic/components` range no longer resolves to 7.4.1.
 */
const overrides = {
	'i18n-calypso': '7.4.0',
};

module.exports = {
	pluginTemplatesPath: join( __dirname, 'variants', 'default' ),
	blockTemplatesPath: join( __dirname, 'variants', 'default', 'src' ),
	defaultValues: {
		npmDependencies: defaultDependencies,
		npmDevDependencies: Object.keys( defaultDevDependencies ),
		customPackageJSON: {
			devDependencies: defaultDevDependencies,
			overrides,
		},
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
