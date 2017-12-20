#!/usr/bin/env node
const config = require( 'config' );
const ServerPilot = require( 'serverpilot' );

const spConfig = config.get( 'spConfig' );

const sp = new ServerPilot( {
	clientId: spConfig.clientId,
	apiKey: spConfig.apiKey
} );

const userConfig = config.get( 'testAccounts' );
const username = userConfig.wooUserCI[0];
const password = userConfig.wooUserCI[1];

const serverPrefix = process.env.TRAVIS_JOB_ID.substr( 0, 20 );
const actionWaitTimeout = 2000;
const serverOptions = {
	name: 'wordpress-' + serverPrefix,
	sysuserid: spConfig.sysuserid,
	runtime: 'php' + process.env.TRAVIS_PHP_VERSION,
	domains: [ serverPrefix + '.wp-e2e-tests.pw' ],
	wordpress: {
		site_title: 'WooCommerce e2e Testing',
		admin_user: username,
		admin_password: password,
		admin_email: 'example@example.com'
	}
};
sp.createApp( serverOptions, ( err, data ) => {
	if ( err !== null ) {
		console.log( err );
		throw err;
	}
	waitForServerPilotAction( data.actionid, ( actionErr ) => {
		if ( err !== null ) {
			console.log( actionErr );
			throw err;
		}
		console.log( 'Site created - http://' + serverPrefix + '.wp-e2e-tests.pw - ID ' + data.data.id );
	} );
} );

/**
 * Waits for ServerPilot to finish an action
 * @param  {String}   actionId The ServerPilot action Id as returned by the create app endpoint
 * @param  {Function} cb       What to do after the action finishes or errors
 */
function waitForServerPilotAction( actionId, cb ) {
	setTimeout( function() {
		sp.getActionStatus( actionId, ( err, response ) => {
			if ( err ) {
				return cb( err );
			}
			if ( response.data.status === 'error' ) {
				return cb( new Error( 'ServerPilot app creation has completed but there were errors.' ) );
			}
			// If ServerPilot is still provisioning the app, recur
			if ( response.data.status === 'open' ) {
				return waitForServerPilotAction( actionId, cb );
			}
			cb();
		} );
	}, actionWaitTimeout );
}
