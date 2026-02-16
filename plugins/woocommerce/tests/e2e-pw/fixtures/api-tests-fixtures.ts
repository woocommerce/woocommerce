const base = require( '@playwright/test' );
const { admin } = require( '../test-data/data' );
const { tags } = require( './fixtures' );

exports.test = base.test.extend( {
	extraHTTPHeaders: {
		// Add authorization token to all requests.
		Authorization: `Basic ${ btoa(
			`${ admin.username }:${ admin.password }`
		) }`,
	},
} );

exports.expect = base.expect;
exports.tags = tags;
exports.request = base.request;
