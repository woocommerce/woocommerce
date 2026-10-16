/**
 * `npmDevDependencies` cannot carry a version: create-block resolves each entry
 * with npm-package-arg and writes `saveSpec || 'latest'`, and `saveSpec` is null
 * for registry specs. Pins therefore have to go through `customPackageJSON`,
 * which is spread over the generated `devDependencies`.
 *
 * This is why the `@wordpress/prettier-config@2.18.2` entry that used to live in
 * `npmDevDependencies` never took effect. It is left at `latest` to preserve the
 * behaviour scaffolds have always had, and because the postinstall pulls
 * wp-prettier 3, which 2.18.2 predates.
 */
const devDependencies = {
	'@woocommerce/dependency-extraction-webpack-plugin': 'latest',
	/* v4 is the first Flat Config release; the scaffolded eslint.config.mjs requires it. */
	'@woocommerce/eslint-plugin': '^4.0.0',
	'@wordpress/prettier-config': 'latest',
	'@wordpress/scripts': 'latest',
};

module.exports = {
	templatesPath: __dirname,
	defaultValues: {
		customScripts: {
			postinstall:
				'npm i --D "prettier@npm:wp-prettier@latest" && npm i --D eslint-plugin-prettier@latest',
		},
		npmDevDependencies: Object.keys( devDependencies ),
		customPackageJSON: {
			files: [ '*.php', 'build', 'block.json' ],
			devDependencies,
		},
	},
};
