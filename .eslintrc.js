module.exports = {
	extends: 'wpcalypso',
	rules: {
		'no-var': 0,
		yoda: [
			'error',
			'always',
		],
	},
	globals: {
		_: false,
		document: false,
		Backbone: false,
		jQuery: false,
		JSON: false,
		wp: false
	},
}
