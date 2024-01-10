const esModules = [
	'simple-html-tokenizer',
	'is-plain-obj',
	'is-plain-object',
	'memize',
	'react-markdown',
].join( '|' );
module.exports = {
	preset: '@wordpress/jest-preset-default',
	rootDir: '../../',
	verbose: true,
	moduleDirectories: [ 'node_modules', '<rootDir>/src' ],
	restoreMocks: true,
	transform: {
		'^.+\\.jsx?$': 'babel-jest',
		[ `(${ esModules }).+\\.js$` ]: 'babel-jest',
	},
	moduleNameMapper: {
		'\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga)$':
			'<rootDir>/tests/js/jest-file-mock.js',
		'^react$': '<rootDir>/node_modules/react',
		'^react-dom$': '<rootDir>/node_modules/react-dom',
		// include react-markdown in the list.
		'^react-markdown$': '<rootDir>/node_modules/react-markdown',
	},
	testPathIgnorePatterns: [
		'/node_modules/',
		'<rootDir>/build/',
		'<rootDir>/.*/build/',
		'<rootDir>/tests',
	],
	transformIgnorePatterns: [ `node_modules/?!(${ esModules })` ],
	testEnvironment: 'jsdom',

	setupFiles: [
		require.resolve(
			'@wordpress/jest-preset-default/scripts/setup-globals.js'
		),
	],
	setupFilesAfterEnv: [
		require.resolve(
			'@wordpress/jest-preset-default/scripts/setup-test-framework.js'
		),
		'<rootDir>/tests/js/jest-setup.js',
	],
};
