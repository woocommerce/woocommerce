module.exports = {
	extends: [ 'plugin:playwright/recommended' ],
	rules: {
		'playwright/no-skipped-test': 'off',
		'no-console': 'off',
		'jest/no-test-callback': 'off',
		'jest/no-disabled-tests': 'off',
		'jest/valid-expect': 'off',
		'jest/expect-expect': 'off',
		'testing-library/await-async-utils': 'off',
	},
};
