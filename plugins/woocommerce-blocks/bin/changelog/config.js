'use strict';

const pkg = require( '../../package.json' );

const REPO = pkg.repository.url
	// remove https://github.com:
	.split( ':' )[ 2 ]
	// remove the .git ending.
	.slice( 0, -4 );

if ( pkg.changelog === undefined ) {
	pkg.changelog = {
		labelPrefix: 'type:',
		skipLabel: 'no-changelog',
		defaultPrefix: 'dev',
		zenhub: false,
	};
}

module.exports = {
	pkg,
	REPO,
};
