const babelOptions = {
	presets: [ '@wordpress/babel-preset-default' ],
};

module.exports = require( 'babel-jest' ).createTransformer( babelOptions );
