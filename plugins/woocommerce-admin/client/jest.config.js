module.exports = {
	rootDir: '../',
	roots: [ '<rootDir>/client' ],
	preset: './node_modules/@woocommerce/internal-js-tests/jest-preset.js',
	globals: {
		'ts-jest': {
			diagnostics: {
				warnOnly: true,
				ignoreCodes: [
					6059, // (TS6059: rootDir is expected to contain all source files) - disabled because some of our tests import directly from ../packages
					18002, // these two are defaults from https://kulshekhar.github.io/ts-jest/docs/getting-started/options/diagnostics
					18003,
				],
			},
			tsconfig: '<rootDir>/client/tsconfig.test.json',
		},
	},
};
