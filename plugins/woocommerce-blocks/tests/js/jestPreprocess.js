const babelOptions = {
	presets: [ '@babel/preset-typescript', '@wordpress/babel-preset-default' ],
	plugins: [ '@babel/plugin-proposal-class-properties' ],
};

module.exports = require( 'babel-jest' ).createTransformer( babelOptions );
