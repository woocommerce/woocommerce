module.exports = {
	testEnvironment: 'node',
	roots: [ '<rootDir>/js' ],
	testMatch: [ '**/test/*.test.js' ],
	transform: {
		'^.+\\.js$': 'babel-jest',
	},
	moduleNameMapper: {
		// Map your module paths if needed
		'^@/(.*)$': '<rootDir>/js/$1',
	},
};
