module.exports = {
	preset: 'ts-jest',
	verbose: true,
	rootDir: 'src',
	testEnvironment: 'node',
	testPathIgnorePatterns: [ '/node_modules/', '/dist/' ],
};
