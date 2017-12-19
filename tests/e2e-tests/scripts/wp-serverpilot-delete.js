#!/usr/bin/env node
const config = require( 'config' );
const ServerPilot = require( 'serverpilot' );

const spConfig = config.get( 'spConfig' );

const sp = new ServerPilot( {
	clientId: spConfig.clientId,
	apiKey: spConfig.apiKey
} );

sp.getApps( ( getErr, data ) => {
	if ( getErr !== null ) {
		console.log( getErr );
		throw getErr;
	}

	const currentApps = data.data.filter( ( app ) => {
		return app.name === `wordpress-${process.env.CIRCLE_SHA1.substr( 0, 20 )}`;
	} );

	// There should only be one, but if not we just silently ignore
	if ( currentApps.length === 1 ) {
		sp.deleteApp( currentApps[0].id, function( delErr ) {
			if ( delErr !== null ) {
				console.log( delErr );
				throw delErr;
			} else {
				console.log( `App ${currentApps[0].id} successfully deleted` );
			}
		} );
	}
} );
