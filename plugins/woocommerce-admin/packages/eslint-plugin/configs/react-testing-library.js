module.exports = {
	extends: [ 'plugin:testing-library/react' ],
	rules: {
		/*
		 *	TODO: remove these rules once we fix all lint errors.
		 *  These rules enabled in eslint-plugin-testing-library lead to new reported *  errors after updating to v5.
		 */
		'testing-library/prefer-query-by-disappearance': 'off',
		'testing-library/render-result-naming-convention': 'off',
		'testing-library/prefer-screen-queries': 'off',
		'testing-library/prefer-presence-queries': 'off',
		'testing-library/no-container': 'off',
		'testing-library/no-node-access': 'off',
		'testing-library/prefer-find-by': 'off',
		// allow the use of render in beforeEach
		'testing-library/no-render-in-setup': [
			'error',
			{ allowTestingFrameworkSetupHook: 'beforeEach' },
		],
	},
};
