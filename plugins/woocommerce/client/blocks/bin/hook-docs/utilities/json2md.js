'use strict';

/**
 * External dependencies
 */
const json2md = require( 'json2md' );

json2md.converters.html = function ( input ) {
	return input;
};

module.exports = { json2md };
