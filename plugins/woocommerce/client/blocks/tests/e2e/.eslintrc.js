const rulesDirPlugin = require( 'eslint-plugin-rulesdir' );
rulesDirPlugin.RULES_DIR = `${ __dirname }/rules`;

const config = {
	extends: [
		'plugin:playwright/recommended',
		'plugin:@typescript-eslint/base',
	],
	plugins: [ 'rulesdir' ],
	parserOptions: {
		tsconfigRootDir: __dirname,
		project: './tsconfig.json',
	},
	rules: {
		'@wordpress/no-global-active-element': 'off',
		'@wordpress/no-global-get-selection': 'off',
		'no-restricted-syntax': [
			'error',
			{
				selector: 'CallExpression[callee.property.name="$"]',
				message: '`$` is discouraged, please use `locator` instead',
			},
			{
				selector: 'CallExpression[callee.property.name="$$"]',
				message: '`$$` is discouraged, please use `locator` instead',
			},
			{
				selector:
					'CallExpression[callee.object.name="page"][callee.property.name="waitForTimeout"]',
				message: 'Prefer page.locator instead.',
			},
		],
		'playwright/no-conditional-in-test': 'off',
		'@typescript-eslint/await-thenable': 'error',
		'@typescript-eslint/no-floating-promises': 'error',
		'@typescript-eslint/no-misused-promises': 'error',
		'rulesdir/no-raw-playwright-test-import': 'error',
		// Since we're restoring the database for each test, hooks other than
		// `beforeEach` don't make sense.
		// See https://github.com/woocommerce/woocommerce/pull/46432.
		'playwright/no-hooks': [ 'error', { allow: [ 'beforeEach' ] } ],
		// Disabled because the there are some tests that are skipped
		// intentionally due to a known bug with needs_shipping
		// 'playwright/no-skipped-test': 'error',
		'playwright/no-useless-not': 'error',
		'playwright/no-wait-for-selector': 'error',
	},
};

module.exports = config;
