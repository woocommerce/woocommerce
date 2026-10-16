/**
 * Internal dependencies
 */
import woocommerce from './index.js';

/*
 * This package is the base that @woocommerce/eslint-config consumes, so it lints
 * itself with its own recommended config rather than the private layer -
 * depending on that layer would be a dependency cycle. That also means it does
 * not get the private layer's relaxations, so the one that applies to this
 * package's own files is repeated here.
 */
export default [
	...woocommerce.configs.recommended,
	{
		/*
		 * These configs are CommonJS by design - index.js requires them - so the
		 * rule typescript-eslint v8 added to its recommended set does not apply.
		 */
		rules: {
			'@typescript-eslint/no-require-imports': 'off',
		},
	},
];
