const babelOptions = {
	presets: [ '@babel/preset-typescript', '@wordpress/babel-preset-default' ],
	plugins: [ 'explicit-exports-references' ],
};

module.exports =
	require( 'babel-jest' ).default.createTransformer( babelOptions );
