/**
 * External dependencies
 */
import { existsSync } from 'fs';
import { readFile } from 'fs/promises';
import path from 'path';
import { glob } from 'glob';
import simpleGit from 'simple-git';

/**
 * Get all projects listed in the workspace yaml file.
 *
 * @param {string} tmpRepoPath   Path to the temporary repository.
 * @param {string} workspaceYaml Contents of the workspace yaml file.
 * @return {Array<string>} List of projects.
 */
export const getAllProjects = async (
	tmpRepoPath: string,
	workspaceYaml: string
) => {
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

/**
 *	Get all projects that have Jetpack changelogger enabled
 *
 * @param {string}        tmpRepoPath Path to the temporary repository.
 * @param {Array<string>} projects    all projects listed in the workspace yaml file
 * @return {Array<string>} List of projects that have Jetpack changelogger enabled.
 */
export const getChangeloggerProjects = async (
	tmpRepoPath: string,
	projects: Array< string >
) => {
	const projectsWithComposer = projects.filter( ( project ) => {
		return existsSync( `${ tmpRepoPath }/${ project }/composer.json` );
	} );
	return projectsWithComposer.filter( async ( project ) => {
		const composer = JSON.parse(
			await readFile(
				`${ tmpRepoPath }/${ project }/composer.json`,
				'utf8'
			)
		);
		return (
			( composer.require &&
				composer.require[ 'automattic/jetpack-changelogger' ] ) ||
			( composer[ 'require-dev' ] &&
				composer[ 'require-dev' ][ 'automattic/jetpack-changelogger' ] )
		);
	} );
};

/**
 * Get an array of all files changed in a PR.
 *
 * @param {string} tmpRepoPath Path to the temporary repository.
 * @param {string} base        base hash
 * @param {string} head        head hash
 * @return {Array<string>} List of files changed in a PR.
 */
export const getTouchedFiles = async (
	tmpRepoPath: string,
	base: string,
	head: string
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
	return diff.split( '\n' ).filter( ( item ) => item.trim() );
};

/**
 * Get an array of projects that have Jetpack changelogger enabled and have files changed in a PR. This function also maps names of projects that have been renamed in the monorepo from their paths.
 *
 * @param {Array<string>} touchedFiles         List of files changed in a PR. touchedFiles
 * @param {Array<string>} changeloggerProjects List of projects that have Jetpack changelogger enabled.
 * @return {Array<object>} List of projects that have Jetpack changelogger enabled and have files changed in a PR.
 */
export const getTouchedChangeloggerProjectsMapped = (
	touchedFiles: Array< string >,
	changeloggerProjects: Array< string >
) => {
	const mappedTouchedFiles = touchedFiles.map( ( touchedProject ) => {
		if ( touchedProject.includes( 'plugins/woocommerce-admin' ) ) {
			return touchedProject.replace(
				'plugins/woocommerce-admin',
				'plugins/woocommerce'
			);
		}
		return touchedProject;
	} );
	const touchedProjectsRequiringChangelogFullPath =
		changeloggerProjects.filter( ( project ) => {
			return mappedTouchedFiles.some( ( touchedProject ) =>
				touchedProject.includes( project + '/' )
			);
		} );
	return touchedProjectsRequiringChangelogFullPath.map( ( project ) => {
		if ( project.includes( 'plugins/' ) ) {
			return {
				project: project.replace( 'plugins/', '' ),
				path: project,
			};
		} else if ( project.includes( 'packages/js/' ) ) {
			return {
				project: project.replace( 'packages/js/', '@woocommerce/' ),
				path: project,
			};
		}
		return { path: project, project };
	} );
};

/**
 * Get an array of projects that have Jetpack changelogger enabled and have files changed in a PR.
 *
 * @param {string} tmpRepoPath Path to the temporary repository.
 * @param {string} base        base hash
 * @param {string} head        head hash
 * @return {Array<object>} List of projects that have Jetpack changelogger enabled and have files changed in a PR.
 */
export const getTouchedProjectsRequiringChangelog = async (
	tmpRepoPath: string,
	base: string,
	head: string
) => {
	const workspaceYaml = await readFile(
		path.join( tmpRepoPath, 'pnpm-workspace.yaml' ),
		'utf8'
	);
	const projects = await getAllProjects( tmpRepoPath, workspaceYaml );
	const changeloggerProjects = await getChangeloggerProjects(
		tmpRepoPath,
		projects
	);
	const touchedFiles = await getTouchedFiles( tmpRepoPath, base, head );

	return getTouchedChangeloggerProjectsMapped(
		touchedFiles,
		changeloggerProjects
	);
};
