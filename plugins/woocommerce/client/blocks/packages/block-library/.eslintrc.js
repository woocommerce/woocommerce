const path = require( 'path' );

const wpBuildUnsafeWooCommerceImports = [
	{
		name: '@woocommerce/base-hooks',
		message:
			'Do not import the base-hooks barrel from block-library. It makes wp-build parse legacy webpack-only files. Move a small helper into packages/block-library/src/shared/ or use an explicitly allowlisted deep import.',
	},
	{
		name: '@woocommerce/base-components',
		message:
			'Do not import base-components from block-library. It can pull legacy webpack-only styles and JSX-in-.js files into wp-build. Use a local wp-build-safe helper/component instead.',
	},
	{
		name: '@woocommerce/shared-hocs',
		message:
			'Do not import the shared-hocs barrel from block-library. It can pull legacy webpack-only dependencies into wp-build. Move the needed HOC into packages/block-library/src/shared/ if it is small.',
	},
	{
		name: '@woocommerce/resource-previews',
		message:
			'Do not import resource-previews from block-library. It includes legacy preview files that wp-build cannot parse reliably.',
	},
	{
		name: '@woocommerce/atomic-utils',
		message:
			'Do not import the atomic-utils barrel from block-library. Use a narrow deep import only when it has been verified as wp-build-safe.',
	},
];

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
		'no-restricted-imports': [
			'error',
			{
				paths: wpBuildUnsafeWooCommerceImports,
			},
		],
	},
};
