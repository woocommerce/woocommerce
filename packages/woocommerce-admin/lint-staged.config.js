module.exports = {
	'*.scss': [ 'npm run lint:css-fix' ],
	'(client|packages)/**/*.js': [
		'wp-scripts format-js',
		'wp-scripts lint-js',
		'npm run test-staged',
	],
	'*.php': [ 'php -d display_errors=1 -l', 'composer run-script phpcs' ],
};
