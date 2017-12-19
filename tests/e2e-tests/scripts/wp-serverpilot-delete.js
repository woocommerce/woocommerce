#!/usr/bin/env node
var config = require( 'config' );
var ServerPilot = require( 'serverpilot' );

var spConfig = config.get( 'spConfig' );

var sp = new ServerPilot( {
	clientId: spConfig.clientId,
	apiKey: spConfig.apiKey
} );

sp.getApps( function( getErr, data ) {
	if ( getErr !== null ) {
		console.log( getErr );
		throw getErr;
	}

	var currentApps = data.data.filter( function( app ) {
		return app.name === 'wordpress-' + process.env.TRAVIS_PULL_REQUEST_SHA.substr( 0, 20 );
	} );

	// There should only be one, but if not we just silently ignore
	if ( currentApps.length === 1 ) {
		sp.deleteApp( currentApps[0].id, function( delErr ) {
			if ( delErr !== null ) {
				console.log( delErr );
				throw delErr;
			} else {
				console.log( 'App ' + currentApps[0].id + ' successfully deleted' );
			}
		} );
	}
} );
