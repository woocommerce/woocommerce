module.exports = {
	launch: {
		// Required for the logged out and logged in tests so they don't share app state/token.
		browserContext: 'incognito',
	},
};
