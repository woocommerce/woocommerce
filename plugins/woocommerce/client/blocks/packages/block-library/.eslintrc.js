const path = require( 'path' );

module.exports = {
	extends: [ '../../.eslintrc.js' ],
	settings: {
		'import/core-modules': [
			'@woocommerce/base-hooks',
			'@woocommerce/base-hooks/use-preview-mode',
		],
		'import/resolver': {
			node: {},
			typescript: {
				project: path.join( __dirname, 'tsconfig.json' ),
			},
		},
	},
	rules: {
		'import/no-extraneous-dependencies': [ 'error' ],
		'no-restricted-syntax': [
			'error',
			{
				selector:
					"ImportDeclaration[source.value='@woocommerce/base-hooks']",
				message:
					'Import hooks from their direct subpath, for example `@woocommerce/base-hooks/use-preview-mode`, so the block-library build does not parse the entire base-hooks barrel.',
			},
		],
	},
};
