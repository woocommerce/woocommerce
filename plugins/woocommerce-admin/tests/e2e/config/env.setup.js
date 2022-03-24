global.process.env = {
	...global.process.env,
	// Gutenberg test util functions expect the test url to be at :8889, we change it to 8084.
	WP_BASE_URL: 'http://localhost:8084',
};
