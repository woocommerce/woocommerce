/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import chalk from 'chalk';
import ora from 'ora';

/**
 * Internal dependencies
 */
import { getLatestReleaseVersion } from '../../../github/repo';
import { octokitWithAuth } from '../../../github/api';
import { setGithubMilestoneOutputs, WPIncrement } from './utils';
import { Options } from './types';

export const milesStoneCommand = new Command( 'milestone' )
	.description( 'Create a milestone' )
	.option(
		'-g --github',
		'CLI command is used in the Github Actions context.'
	)
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
	.action( async ( options: Options ) => {
		const { owner, name, dryRun, milestone, github } = options;

		if ( milestone && github ) {
			console.log(
				chalk.red(
					"You can't manually supply a milestone using Github mode. Please use the CLI locally to add a milestone."
				)
			);
			process.exit( 1 );
		}

		let nextMilestone;
		let nextReleaseVersion;

		if ( milestone ) {
			console.log(
				chalk.yellow(
					`Manually creating milestone ${ milestone } in ${ owner }/${ name }`
				)
			);
			nextMilestone = milestone;
		} else {
			const versionSpinner = ora(
				'No milestone supplied, going off the latest release version'
			).start();
			const latestReleaseVersion = await getLatestReleaseVersion(
				options
			);
			versionSpinner.succeed();

			nextReleaseVersion = WPIncrement( latestReleaseVersion );
			nextMilestone = WPIncrement( nextReleaseVersion );

			console.log(
				chalk.yellow(
					`The latest release in ${ owner }/${ name } is version: ${ latestReleaseVersion }`
				)
			);

			console.log(
				chalk.yellow(
					`The next release in ${ owner }/${ name } will be version: ${ nextReleaseVersion }`
				)
			);

			console.log(
				chalk.yellow(
					`The next milestone in ${ owner }/${ name } will be: ${ nextMilestone }`
				)
			);
		}

		const milestoneSpinner = ora(
			`Creating a ${ nextMilestone } milestone`
		).start();

		if ( dryRun ) {
			milestoneSpinner.succeed();
			console.log(
				chalk.green(
					`DRY RUN: Skipping actual creation of milestone ${ nextMilestone }`
				)
			);

			process.exit( 0 );
		}

		try {
			await octokitWithAuth.request(
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
				console.log(
					chalk.green(
						`Milestone ${ nextMilestone } already exists in ${ owner }/${ name }`
					)
				);
				if ( github ) {
					setGithubMilestoneOutputs(
						nextReleaseVersion,
						nextMilestone
					);
				}
				process.exit( 0 );
			} else {
				milestoneSpinner.fail();
				console.log(
					chalk.red(
						`\nFailed to create milestone ${ nextMilestone } in ${ owner }/${ name }`
					)
				);
				console.log( chalk.red( e.response.data.message ) );
				process.exit( 1 );
			}
		}

		milestoneSpinner.succeed();
		if ( github ) {
			setGithubMilestoneOutputs( nextReleaseVersion, nextMilestone );
		}
		console.log(
			chalk.green(
				`Successfully created milestone ${ nextMilestone } in ${ owner }/${ name }`
			)
		);
	} );
