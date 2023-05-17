/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';
import { glob } from 'glob';
import simpleGit from 'simple-git';

const getAllProjects = async ( tmpRepoPath: string ) => {
	const workspaceYaml = fs.readFileSync(
		path.join( tmpRepoPath, 'pnpm-workspace.yaml' ),
		'utf8'
	);
	const rawProjects = workspaceYaml.split( '- ' );
	// remove heading
	rawProjects.shift();

	const globbedProjects = await Promise.all(
		rawProjects
			.map( ( project ) => project.replace( /'/g, '' ).trim() )
			.map( async ( project ) => {
				if ( project.includes( '*' ) ) {
					return await glob( project, { cwd: tmpRepoPath } );
				}
				return project;
			} )
	);
	return globbedProjects.flat();
};

export const getChangeloggerProjects = async ( tmpRepoPath: string ) => {
	const projects = await getAllProjects( tmpRepoPath );
	const projectsWithComposer = projects.filter( ( project ) => {
		return fs.existsSync( `${ tmpRepoPath }/${ project }/composer.json` );
	} );
	return projectsWithComposer.filter( ( project ) => {
		const composer = JSON.parse(
			fs.readFileSync(
				`${ tmpRepoPath }/${ project }/composer.json`,
				'utf8'
			)
		);
		return (
			( composer.require &&
				composer.require[ 'automattic/jetpack-changelogger' ] ) ||
			composer[ 'require-dev' ][ 'automattic/jetpack-changelogger' ]
		);
	} );
};

export const getTouchedProjects = async (
	tmpRepoPath,
	base,
	head,
	fileName
) => {
	const git = simpleGit( {
		baseDir: tmpRepoPath,
		config: [ 'core.hooksPath=/dev/null' ],
	} );

	// make sure base sha is available.
	await git.raw( [
		'remote',
		'add',
		'woocommerce',
		'git@github.com:woocommerce/woocommerce.git',
	] );
	await git.raw( [ 'fetch', 'woocommerce', base ] );
	const diff = await git.raw( [
		'diff',
		'--name-only',
		`${ base }...${ head }`,
	] );
	return diff
		.split( '\n' )
		.filter( ( item ) => item.trim() )
		.filter( ( item ) => ! item.includes( `changelog/${ fileName }` ) );
};

export const getTouchedProjectsRequiringChangelog = async (
	tmpRepoPath,
	base,
	head,
	fileName
) => {
	// Take into account woocommerce-admin changes get a woocommerce changelog
	const changeloggerProjects = await getChangeloggerProjects( tmpRepoPath );
	const touchedProjects = await getTouchedProjects(
		tmpRepoPath,
		base,
		head,
		fileName
	);

	const mappedTouchedProjects = touchedProjects.map( ( touchedProject ) => {
		if ( touchedProject.includes( 'plugins/woocommerce-admin' ) ) {
			return touchedProject.replace(
				'plugins/woocommerce-admin',
				'plugins/woocommerce'
			);
		}
		return touchedProject;
	} );
	return changeloggerProjects.filter( ( project ) => {
		return mappedTouchedProjects.some( ( touchedProject ) =>
			touchedProject.includes( project + '/' )
		);
	} );
};
