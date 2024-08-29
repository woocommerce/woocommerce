module.exports = {
	preset: '@wordpress/jest-preset-default',
	rootDir: '../../',
	verbose: true,
	moduleDirectories: [ 'node_modules', '<rootDir>/src' ],
	restoreMocks: true,
	transform: {
		'^.+\\.jsx?$': 'babel-jest',
	},
	moduleNameMapper: {
		'\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga)$':
			'<rootDir>/tests/js/jest-file-mock.js',
		'^react$': '<rootDir>/node_modules/react',
		'^react-dom$': '<rootDir>/node_modules/react-dom',
	},
	testPathIgnorePatterns: [
		'/node_modules/',
		'<rootDir>/build/',
		'<rootDir>/.*/build/',
		'<rootDir>/vendor',
		'<rootDir>/tests',
	],
	transformIgnorePatterns: [
		'node_modules/?!(simple-html-tokenizer|is-plain-obj|is-plain-object|memize)',
	],
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
