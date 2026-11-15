'use strict';

/**
 * External dependencies
 */
const json2md = require( 'json2md' );

json2md.converters.html = function ( input ) {
	return input;
};

// json2md's stock `ul` converter indents items with a leading space, which
// markdownlint (MD007, indent 0 at top level) rejects. Emit flat lists instead.
json2md.converters.ul = function ( input ) {
	return input.map( ( item ) => `- ${ item }` ).join( '\n' );
};

module.exports = { json2md };
