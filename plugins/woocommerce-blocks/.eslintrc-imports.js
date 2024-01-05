module.exports = {
	parser: '@typescript-eslint/parser', // Specifies the ESLint parser
	extends: [
		'plugin:import/errors',
		'plugin:import/warnings',
		'plugin:import/typescript',
	],
	settings: {
		'import/parsers': {
			'@typescript-eslint/parser': [ '.ts', '.tsx' ],
		},
		'import/resolver': {
			typescript: {},
		},
		'import/core-modules': [
			// We should lint these modules imports, but the types are way out of date.
			// To support us not inadvertently introducing new import errors this lint exists, but to avoid
			// having to fix hundreds of import errors for @wordpress packages we ignore them.
			'@wordpress/components',
			'@wordpress/element',
			'@wordpress/blocks',
			'@wordpress/notices',
		],
	},
	rules: {
		'import/named': 'error',
		//  These should absolutely be linted, but due to there being a large number
		//  of changes needed to fix for example `export *` of packages with only default exports
		//  we will leave these as warnings for now until those can be fixed.
		'import/namespace': 'warn',
		'import/export': 'warn',
	},
};
