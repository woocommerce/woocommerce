/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { DateTime } from 'luxon';
import { setOutput } from '@actions/core';
import chalk from 'chalk';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { isGithubCI } from '../../../core/environment';
import {
	getToday,
	getMonthlyCycle,
	getAcceleratedCycle,
	getVersionsBetween,
} from './lib/index';

const getRange = ( override, between ) => {
	if ( isGithubCI() ) {
		Logger.error(
			'-b, --between option is not compatible with GitHub CI Output.'
		);
		process.exit( 1 );
	}

	const today = getToday( override );
	const end = getToday( between );
	const versions = getVersionsBetween( today, end );

	Logger.notice(
		chalk.greenBright.bold(
			`Releases Between ${ today.toFormat( 'DDDD' ) } and ${ end.toFormat(
				'DDDD'
			) }\n`
		)
	);

	Logger.table(
		[ 'Version', 'Development Begins', 'Freeze', 'Release' ],
		versions.map( ( v ) =>
			Object.values( v ).map( ( d: DateTime | string ) =>
				typeof d.toFormat === 'function'
					? d.toFormat( 'EEE, MMM dd, yyyy' )
					: d
			)
		)
	);

	process.exit( 0 );
};

export const getVersionCommand = new Command( 'get-version' )
	.description( 'Get the release calendar for a given date' )
	.option(
		'-o, --override <override>',
		"Time Override: The time to use in checking whether the action should run (default: 'now').",
		'now'
	)
	.option(
		'-b, --between <between>',
		'When provided, instead of showing a single day, will show a releases in the range of <override> to <end>.'
	)
	.action( ( { override, between } ) => {
		if ( between ) {
			return getRange( override, between );
		}

		const today = getToday( override );
		const acceleratedRelease = getAcceleratedCycle( today, false );
		const acceleratedDevelopment = getAcceleratedCycle( today );
		const monthlyRelease = getMonthlyCycle( today, false );
		const monthlyDevelopment = getMonthlyCycle( today );

		// Generate human-friendly output.
		Logger.notice(
			chalk.greenBright.bold(
				`Release Calendar for ${ today.toFormat( 'DDDD' ) }\n`
			)
		);
		const table = [];
		// We're not in a release cycle on Wednesday.
		if ( today.get( 'weekday' ) !== 3 ) {
			table.push( [
				`${ chalk.red( 'Accelerated Release Cycle' ) }`,
				acceleratedRelease.version,
				acceleratedRelease.begin.toFormat( 'EEE, MMM dd, yyyy' ),
				acceleratedRelease.freeze.toFormat( 'EEE, MMM dd, yyyy' ),
				acceleratedRelease.release.toFormat( 'EEE, MMM dd, yyyy' ),
			] );
		}
		table.push( [
			`${ chalk.red( 'Accelerated Development Cycle' ) }`,
			acceleratedDevelopment.version,
			acceleratedDevelopment.begin.toFormat( 'EEE, MMM dd, yyyy' ),
			acceleratedDevelopment.freeze.toFormat( 'EEE, MMM dd, yyyy' ),
			acceleratedDevelopment.release.toFormat( 'EEE, MMM dd, yyyy' ),
		] );
		// We're only in a release cycle if it is after the freeze day.
		if ( today > monthlyRelease.freeze ) {
			table.push( [
				`${ chalk.red( 'Monthly Release Cycle' ) }`,
				monthlyRelease.version,
				monthlyRelease.begin.toFormat( 'EEE, MMM dd, yyyy' ),
				monthlyRelease.freeze.toFormat( 'EEE, MMM dd, yyyy' ),
				monthlyRelease.release.toFormat( 'EEE, MMM dd, yyyy' ),
			] );
		}
		table.push( [
			`${ chalk.red( 'Monthly Development Cycle' ) }`,
			monthlyDevelopment.version,
			monthlyDevelopment.begin.toFormat( 'EEE, MMM dd, yyyy' ),
			monthlyDevelopment.freeze.toFormat( 'EEE, MMM dd, yyyy' ),
			monthlyDevelopment.release.toFormat( 'EEE, MMM dd, yyyy' ),
		] );
		Logger.table(
			[ '', 'Version', 'Development Begins', 'Freeze', 'Release' ],
			table
		);

		if ( isGithubCI() ) {
			// For the machines.
			const isTodayAcceleratedFreeze = today.get( 'weekday' ) === 4;
			const isTodayMonthlyFreeze = +today === +monthlyDevelopment.begin;
			const monthlyVersionXY = monthlyRelease.version.substr(
				0,
				monthlyRelease.version.lastIndexOf( '.' )
			);
			setOutput(
				'isTodayAcceleratedFreeze',
				isTodayAcceleratedFreeze ? 'yes' : 'no'
			);
			setOutput(
				'isTodayMonthlyFreeze',
				'yes'
			);
			setOutput( 'acceleratedVersion', acceleratedRelease.version );
			setOutput( 'monthlyVersion', monthlyRelease.version );
			setOutput( 'monthlyVersionXY', monthlyVersionXY );
			setOutput(
				'releasesFrozenToday',
				JSON.stringify(
					Object.values( {
						...( isTodayMonthlyFreeze && {
							monthlyVersion: `${ monthlyRelease.version } (Monthly)`,
						} ),
						...( isTodayAcceleratedFreeze && {
							aVersion: `${ acceleratedRelease.version } (AF)`,
						} ),
					} )
				)
			);
			setOutput(
				'acceleratedBranch',
				`release/${ acceleratedRelease.version }`
			);
			setOutput( 'monthlyBranch', `release/${ monthlyVersionXY }` );
			setOutput( 'monthlyMilestone', monthlyDevelopment.version );
			setOutput(
				'acceleratedReleaseDate',
				acceleratedRelease.release.toISODate()
			);
		}

		process.exit( 0 );
	} );
