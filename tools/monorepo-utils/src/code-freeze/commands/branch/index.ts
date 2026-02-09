/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { parse } from 'semver';
import { confirm } from 'promptly';
import chalk from 'chalk';
import ora from 'ora';
import { setOutput } from '@actions/core';

/**
 * Internal dependencies
 */
import {
	getLatestGithubReleaseVersion,
	doesGithubBranchExist,
	getRefFromGithubBranch,
	createGithubBranch,
	deleteGithubBranch,
} from '../../../core/github/repo';
import { WPIncrement } from '../../../core/version';
import { Logger } from '../../../core/logger';
import { isGithubCI } from '../../../core/environment';

const getNextReleaseBranch = async ( options: {
	owner?: string;
	name?: string;
} ) => {
	const latestReleaseVersion = await getLatestGithubReleaseVersion( options );
	const nextReleaseVersion = WPIncrement( latestReleaseVersion );
	const parsedNextReleaseVersion = parse( nextReleaseVersion );
	const nextReleaseMajorMinor = `${ parsedNextReleaseVersion.major }.${ parsedNextReleaseVersion.minor }`;
	return `release/${ nextReleaseMajorMinor }`;
};

export const branchCommand = new Command( 'branch' )
	.description( 'Create a new release branch' )
	.option( '-d --dryRun', 'Prepare the branch but do not create it.' )
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
		'-b --branch <branch>',
		'Release branch to create. The branch will be determined from Github if none is supplied'
	)
	.option(
		'-s --source <source>',
		'Branch to create the release branch from. Default: trunk',
		'trunk'
	)
	.action( async ( options ) => {
		const { source, branch, owner, name, dryRun } = options;
		const isGithub = isGithubCI();

		let nextReleaseBranch;

		if ( ! branch ) {
			const versionSpinner = ora(
				chalk.yellow(
					'No branch supplied, going off the latest release version'
				)
			).start();
			nextReleaseBranch = await getNextReleaseBranch( options );
			Logger.warn( `The next release branch is ${ nextReleaseBranch }` );
			versionSpinner.succeed();
		} else {
			nextReleaseBranch = branch;
		}

		const branchSpinner = ora(
			chalk.yellow(
				`Check to see if branch ${ nextReleaseBranch } exists on ${ owner }/${ name }`
			)
		).start();

		const branchExists = await doesGithubBranchExist(
			options,
			nextReleaseBranch
		);
		branchSpinner.succeed();

		if ( branchExists ) {
			if ( isGithub ) {
				Logger.error(
					`Release branch ${ nextReleaseBranch } already exists`
				);
				// When in Github Actions, we don't want to prompt the user for input.
				process.exit( 0 );
			}
			const deleteExistingReleaseBranch = await confirm(
				chalk.yellow(
					`Release branch ${ nextReleaseBranch } already exists on ${ owner }/${ name }, do you want to delete it and create a new one from ${ source }? [y/n]`
				)
			);
			if ( deleteExistingReleaseBranch ) {
				if ( ! dryRun ) {
					const deleteBranchSpinner = ora(
						chalk.yellow(
							`Delete branch ${ nextReleaseBranch } on ${ owner }/${ name } and create new one from ${ source }`
						)
					).start();
					await deleteGithubBranch( options, nextReleaseBranch );
					deleteBranchSpinner.succeed();
				}
			} else {
				Logger.notice(
					`Branch ${ nextReleaseBranch } already exist on ${ owner }/${ name }, no action taken.`
				);
				process.exit( 0 );
			}
		}

		const createBranchSpinner = ora(
			chalk.yellow( `Create branch ${ nextReleaseBranch }` )
		).start();

		if ( dryRun ) {
			createBranchSpinner.succeed();
			Logger.notice(
				`DRY RUN: Skipping actual creation of branch ${ nextReleaseBranch } on ${ owner }/${ name }`
			);

			process.exit( 0 );
		}

		const ref = await getRefFromGithubBranch( options, source );
		await createGithubBranch( options, nextReleaseBranch, ref );
		createBranchSpinner.succeed();

		if ( isGithub ) {
			setOutput( 'nextReleaseBranch', nextReleaseBranch );
		}

		Logger.notice(
			`Branch ${ nextReleaseBranch } successfully created on ${ owner }/${ name }`
		);
	} );
