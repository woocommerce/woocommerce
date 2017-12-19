#!/usr/bin/env node
'use strict';
var config = require( 'config' );
var ServerPilot = require( 'serverpilot' );

var spConfig = config.get( 'spConfig' );

var sp = new ServerPilot( {
	clientId: spConfig.clientId,
	apiKey: spConfig.apiKey
} );

var userConfig = config.get( 'testAccounts' );
var username = userConfig.wooUserCI[0];
var password = userConfig.wooUserCI[1];

var serverPrefix = process.env.TRAVIS_PULL_REQUEST_SHA.substr( 0, 20 );
var actionWaitTimeout = 2000;

var serverOptions = {
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
sp.createApp( serverOptions, function( err, data ) {
	if ( err !== null ) {
		console.log( err );
		throw err;
	}
	waitForServerPilotAction( data.actionid, function( actionErr ) {
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
		sp.getActionStatus( actionId, function( err, response ) {
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
