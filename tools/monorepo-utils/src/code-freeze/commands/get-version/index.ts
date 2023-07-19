/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { setOutput } from '@actions/core';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { isGithubCI } from '../../../core/environment';
import { getVersion, getVersionMonthly } from './lib/index';

export const getVersionCommand = new Command( 'get-version' )
	.description( 'Get the versions (a and monthly) for a given date' )
	.option(
		'-o, --override <override>',
		"Time Override: The time to use in checking whether the action should run (default: 'now').",
		'now'
	)
	.action( ( { override } ) => {
		const aVersion = getVersion( override );
		const monthlyVersion = getVersionMonthly( override );
		Logger.notice( `A Version: ${ aVersion }` );
		Logger.notice( `Monthly Version: ${ monthlyVersion }` );

		if ( isGithubCI ) {
			setOutput( 'aVersion', aVersion );
			setOutput( 'monthlyVersion', monthlyVersion );
		}

		process.exit( 0 );
	} );
