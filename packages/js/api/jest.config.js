module.exports = {
	preset: 'ts-jest',
	verbose: true,
	rootDir: 'src',
	testEnvironment: 'node',
	testPathIgnorePatterns: [ 
		'/node_modules/',
		'/dist/'
	],
	transform: {
		'^.+\\.ts$': 'ts-jest',
		'node_modules/axios/lib/.+\\.js$': 'babel-jest',
	},
	transformIgnorePatterns: [
		'node_modules/(?!(?:.pnpm/)?axios)',
	],
};
