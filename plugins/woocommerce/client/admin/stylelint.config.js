module.exports = {
	extends: '../../../../stylelint.config.cjs',
	ignoreFiles: [ './vendor/**/*.scss' ],
	rules: {
		'scss/load-partial-extension': 'always',
	},
};
