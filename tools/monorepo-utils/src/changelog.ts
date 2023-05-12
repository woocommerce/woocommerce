/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';
import { readFile, writeFile } from 'fs/promises';
import path from 'path';

/**
 * Internal dependencies
 */
import { Logger } from './core/logger';
import { cloneAuthenticatedRepo, checkoutRemoteBranch } from './core/git';

const changeLogHelper = new Command( 'changelog' )
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
		'-d --dev-repo-path <devRepoPath>',
		'Path to existing repo. Use this option to avoid cloning a fresh repo for development purposes. Note that using this option assumes dependencies are already installed.'
	)
	.action(
		async ( options: {
			owner: string;
			name: string;
			devRepoPath?: string;
		} ) => {
			const { owner, name, devRepoPath } = options;

			Logger.startTask(
				`Making a temporary clone of '${ owner }/${ name }'`
			);
			const tmpRepoPath = devRepoPath
				? devRepoPath
				: await cloneAuthenticatedRepo( options, true );

			Logger.endTask();

			Logger.notice(
				`Temporary clone of '${ owner }/${ name }' created at ${ tmpRepoPath }`
			);

			const branch = 'test/change';

			Logger.notice( `Checking out branch ${ branch }` );
			await checkoutRemoteBranch( tmpRepoPath, branch );

			const readmeFile = path.join(
				tmpRepoPath,
				'plugins',
				'woocommerce',
				'readme.txt'
			);

			Logger.notice( `Updating ${ readmeFile }` );
			const readme = await readFile( readmeFile, 'utf-8' );
			await writeFile( readmeFile, readme + 'THIS IS A CHANGE' );

			const git = simpleGit( {
				baseDir: tmpRepoPath,
				config: [ 'core.hooksPath=/dev/null' ],
			} );
			await git.raw(
				'config',
				'--global',
				'user.email',
				'psealock@gmail.com'
			);
			await git.raw( 'config', '--global', 'user.name', 'paul sealock' );
			Logger.notice( `Adding and committing changes` );
			await git.add( '.' );
			await git.commit( 'test' );
			await git.push( 'origin', branch );

			Logger.notice( `Pushed changes to ${ branch }` );
		}
	);

export default changeLogHelper;
