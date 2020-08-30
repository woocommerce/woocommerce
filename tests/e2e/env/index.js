// Internal dependencies
const babelConfig = require( './babel.config' );
const esLintConfig = require( './.eslintrc.js' );
const jestConfig = require( './config/jest.config.js' );
const webpackAlias = require( './webpack-alias' );

module.exports = {
    babelConfig,
    esLintConfig,
    jestConfig,
    webpackAlias,
};
