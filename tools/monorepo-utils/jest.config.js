/** @type {import('ts-jest').JestConfigWithTsJest} */
module.exports = {
	preset: 'ts-jest',
	testEnvironment: 'node',
	testPathIgnorePatterns: [ '<rootDir>/dist/', '<rootDir>/node_modules/' ],
};
