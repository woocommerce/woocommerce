const { join } = require( 'path' );

const defaultDependencies = [
	'@wordpress/components',
	'@wordpress/element',
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
	 * Match the `react` and `react-dom` peer range of `@woocommerce/components`
	 * (packages/js/components/package.json) instead of npm selecting React 19.
	 * Update these when that range changes.
	 */
	react: '18.3.x',
	'react-dom': '18.3.x',
	/*
	 * Without this, `@wordpress/prettier-config`'s `prettier: >=3` peer hoists plain
	 * prettier over wp-prettier. Pinned to match `@woocommerce/eslint-plugin`.
	 */
	prettier: 'npm:wp-prettier@3.9.6',
};

/*
 * Stopgap: `i18n-calypso@7.4.1` (via `@woocommerce/components`) ships a Yarn-only
 * `patch:` spec that breaks `npm install`. Pin to 8.1.0 (latest, without the
 * spec) until the upstream range stops resolving to 7.4.1.
 */
const overrides = {
	'i18n-calypso': '8.1.0',
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
				// The add-task template imports these directly; declare them to satisfy
				// import/no-extraneous-dependencies without relying on transitive dependencies.
				'@woocommerce/data',
				'@wordpress/data',
				'@wordpress/plugins',
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
			npmDependencies: [
				...defaultDependencies,
				'@woocommerce/currency',
				'@wordpress/icons',
				'moment',
			],
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
