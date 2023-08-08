module.exports = {
	exitOnPageError: false,
	launch: {
		ignoreDefaultArgs: [ '--disable-extensions' ],
		args: [ '--no-sandbox', '--disable-dev-shm-usage' ],
	},
};
