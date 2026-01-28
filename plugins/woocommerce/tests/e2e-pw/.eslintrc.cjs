module.exports = {
	extends: [ 'plugin:playwright/recommended' ],
	rules: {
		'playwright/no-wait-for-timeout': 'error',
		'playwright/no-skipped-test': 'off',
		'no-console': 'off',
		'jest/no-test-callback': 'off',
		'jest/no-disabled-tests': 'off',
		'jest/valid-expect': 'off',
		'jest/expect-expect': 'off',
		'jest/no-standalone-expect': 'off',
		'jest/valid-title': 'off',
		'testing-library/await-async-utils': 'off',
	},
};
