/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import ora from 'ora';

/**
 * Internal dependencies
 */
import { getLatestGithubReleaseVersion } from '../../../core/github/repo';
import { octokitWithAuth } from '../../../core/github/api';
import { WPIncrement } from '../../../core/version';
import { Logger } from '../../../core/logger';

export const milestoneCommand = new Command( 'milestone' )
	.description( 'Create a milestone' )
	.option( '-d --dryRun', 'Prepare the milestone but do not create it.' )
	.option(
		'-o --owner <owner>',
		'Repository owner. Default: woocommerce',
		'woocommerce'
	)
	.option(
		'-n --name <name>',
		'Repository name. Default: woocommerce',
		'woocommerce'
	)
	.option(
		'-m --milestone <milestone>',
		'Milestone to create. Next milestone is gathered from Github if none is supplied'
	)
	.action( async ( options ) => {
		const { owner, name, dryRun, milestone } = options;

		let nextMilestone;
		let nextReleaseVersion;

		if ( milestone ) {
			Logger.warn(
				`Manually creating milestone ${ milestone } in ${ owner }/${ name }`
			);
			nextMilestone = milestone;
		} else {
			const versionSpinner = ora(
				'No milestone supplied, going off the latest release version'
			).start();
			const latestReleaseVersion = await getLatestGithubReleaseVersion(
				options
			);
			versionSpinner.succeed();

			nextReleaseVersion = WPIncrement( latestReleaseVersion );
			nextMilestone = WPIncrement( nextReleaseVersion );

			Logger.warn(
				`The latest release in ${ owner }/${ name } is version: ${ latestReleaseVersion }`
			);

			Logger.warn(
				`The next release in ${ owner }/${ name } will be version: ${ nextReleaseVersion }`
			);

			Logger.warn(
				`The next milestone in ${ owner }/${ name } will be: ${ nextMilestone }`
			);
		}

		const milestoneSpinner = ora(
			`Creating a ${ nextMilestone } milestone`
		).start();

		if ( dryRun ) {
			milestoneSpinner.succeed();
			Logger.notice(
				`DRY RUN: Skipping actual creation of milestone ${ nextMilestone }`
			);

			process.exit( 0 );
		}

		try {
			await octokitWithAuth().request(
				`POST /repos/${ owner }/${ name }/milestones`,
				{
					title: nextMilestone,
				}
			);
		} catch ( e ) {
			const milestoneAlreadyExistsError = e.response.data.errors?.some(
				( error ) => error.code === 'already_exists'
			);

			if ( milestoneAlreadyExistsError ) {
				milestoneSpinner.succeed();
				Logger.notice(
					`Milestone ${ nextMilestone } already exists in ${ owner }/${ name }`
				);
				process.exit( 0 );
			} else {
				milestoneSpinner.fail();
				Logger.error(
					`\nFailed to create milestone ${ nextMilestone } in ${ owner }/${ name }`
				);
				Logger.error( e.response.data.message );
				process.exit( 1 );
			}
		}

		milestoneSpinner.succeed();

		Logger.notice(
			`Successfully created milestone ${ nextMilestone } in ${ owner }/${ name }`
		);
	} );
