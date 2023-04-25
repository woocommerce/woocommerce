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
	getLatestReleaseVersion,
	doesGithubBranchExist,
	getRefFromGithubBranch,
	createGithubBranch,
	deleteGithubBranch,
} from '../../../github/repo';
import { WPIncrement } from '../milestone/utils';
import { Options } from './types';

const getNextReleaseBranch = async ( options: Options ) => {
	const latestReleaseVersion = await getLatestReleaseVersion( options );
	const nextReleaseVersion = WPIncrement( latestReleaseVersion );
	const parsedNextReleaseVersion = parse( nextReleaseVersion );
	const nextReleaseMajorMinor = `${ parsedNextReleaseVersion.major }.${ parsedNextReleaseVersion.minor }`;
	return `release/${ nextReleaseMajorMinor }`;
};

export const branchCommand = new Command( 'branch' )
	.description( 'Create a new release branch' )
	.option(
		'-g --github',
		'CLI command is used in the Github Actions context.'
	)
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
	.action( async ( options: Options ) => {
		const { github, source, branch, owner, name, dryRun } = options;
		let nextReleaseBranch;

		if ( ! branch ) {
			const versionSpinner = ora(
				chalk.yellow(
					'No branch supplied, going off the latest release version'
				)
			).start();
			nextReleaseBranch = await getNextReleaseBranch( options );
			console.log(
				chalk.yellow(
					`The next release branch is ${ nextReleaseBranch }`
				)
			);
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
			if ( github ) {
				console.log(
					chalk.red(
						`Release branch ${ nextReleaseBranch } already exists`
					)
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
				const deleteBranchSpinner = ora(
					chalk.yellow(
						`Delete branch ${ nextReleaseBranch } on ${ owner }/${ name } and create new one from ${ source }`
					)
				).start();
				await deleteGithubBranch( options, nextReleaseBranch );
				deleteBranchSpinner.succeed();
			} else {
				console.log(
					chalk.green(
						`Branch ${ nextReleaseBranch } already exist on ${ owner }/${ name }, no action taken.`
					)
				);
				process.exit( 0 );
			}
		}

		const createBranchSpinner = ora(
			chalk.yellow( `Create branch ${ nextReleaseBranch }` )
		).start();

		if ( dryRun ) {
			createBranchSpinner.succeed();
			console.log(
				chalk.green(
					`DRY RUN: Skipping actual creation of branch ${ nextReleaseBranch } on ${ owner }/${ name }`
				)
			);

			process.exit( 0 );
		}

		const ref = await getRefFromGithubBranch( options, source );
		await createGithubBranch( options, nextReleaseBranch, ref );
		createBranchSpinner.succeed();

		if ( github ) {
			setOutput( 'nextReleaseBranch', nextReleaseBranch );
		}

		console.log(
			chalk.green(
				`Branch ${ nextReleaseBranch } successfully created on ${ owner }/${ name }`
			)
		);
	} );
