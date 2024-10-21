/**
 * External dependencies
 */
import { existsSync, readFileSync } from 'fs';
import { readFile } from 'fs/promises';
import path from 'path';
import { glob } from 'glob';
import simpleGit from 'simple-git';

/**
 * Internal dependencies
 */
import { getAuthenticatedRemote } from '../../core/git';
/**
 * Get all projects listed in the workspace yaml file.
 *
 * @param {string} tmpRepoPath   Path to the temporary repository.
 * @param {string} workspaceYaml Contents of the workspace yaml file.
 * @return {Array<string>} List of projects.
 */
export const getAllProjectsPathsFromWorkspace = async (
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
	const r = globbedProjects.flat();
	return r;
};

/**
 *	Get all projects that have Jetpack changelogger enabled
 *
 * @param {string}        tmpRepoPath Path to the temporary repository.
 * @param {Array<string>} projects    all projects listed in the workspace yaml file
 * @return {Array<string>} List of projects that have Jetpack changelogger enabled.
 */
export const getChangeloggerProjectPaths = async (
	tmpRepoPath: string,
	projects: Array< string >
) => {
	const projectsWithComposer = projects.filter( ( project ) => {
		return existsSync( `${ tmpRepoPath }/${ project }/composer.json` );
	} );
	return projectsWithComposer.filter( ( project ) => {
		const composer = JSON.parse(
			readFileSync(
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
 * @param {string} fileName    changelog file name
 * @param {string} baseOwner   PR base owner
 * @param {string} baseName    PR base name
 * @return {Array<string>} List of files changed in a PR.
 */
export const getTouchedFilePaths = async (
	tmpRepoPath: string,
	base: string,
	head: string,
	fileName: string,
	baseOwner: string,
	baseName: string
) => {
	const git = simpleGit( {
		baseDir: tmpRepoPath,
		config: [ 'core.hooksPath=/dev/null' ],
	} );

	// make sure base sha is available.
	await git.addRemote(
		baseOwner,
		getAuthenticatedRemote( { owner: baseOwner, name: baseName } )
	);
	await git.fetch( baseOwner, base );

	const diff = await git.raw( [
		'diff',
		'--name-only',
		`${ base }...${ head }`,
	] );
	return (
		diff
			.split( '\n' )
			.map( ( item ) => item.trim() )
			// Don't count changelogs themselves as touched files.
			.filter( ( item ) => ! item.includes( `/changelog/${ fileName }` ) )
	);
};

/**
 * Get an array of projects that have Jetpack changelogger enabled and have files changed in a PR. This function also maps names of projects that have been renamed in the monorepo from their paths.
 *
 * @param {Array<string>} touchedFiles         List of files changed in a PR. touchedFiles
 * @param {Array<string>} changeloggerProjects List of projects that have Jetpack changelogger enabled.
 * @return {Object.<string, string>} Paths to projects that have files changed in a PR keyed by the project name.
 */
export const getTouchedChangeloggerProjectsPathsMappedToProjects = (
	touchedFiles: Array< string >,
	changeloggerProjects: Array< string >
) => {
	const touchedProjectPathsRequiringChangelog = changeloggerProjects.filter(
		( project ) => {
			return touchedFiles.some( ( touchedProject ) =>
				touchedProject.includes( project + '/' )
			);
		}
	);

	const projectPaths = {};
	for ( const projectPath of touchedProjectPathsRequiringChangelog ) {
		let project = projectPath;
		if ( project.includes( 'plugins/' ) ) {
			project = project.replace( 'plugins/', '' );
		} else if ( project.includes( 'packages/js/' ) ) {
			project = project.replace( 'packages/js/', '@woocommerce/' );
		}

		projectPaths[ project ] = projectPath;
	}
	return projectPaths;
};

/**
 * Get all projects listed in the workspace yaml file.
 *
 * @param {string} tmpRepoPath Path to the temporary repository.
 * @return {Array<string>} List of projects.
 */
export const getAllProjectPaths = async ( tmpRepoPath: string ) => {
	const workspaceYaml = await readFile(
		path.join( tmpRepoPath, 'pnpm-workspace.yaml' ),
		'utf8'
	);
	return await getAllProjectsPathsFromWorkspace( tmpRepoPath, workspaceYaml );
};

/**
 * Get an array of projects that have Jetpack changelogger enabled and have files changed in a PR.
 *
 * @param {string} tmpRepoPath Path to the temporary repository.
 * @param {string} base        base hash
 * @param {string} head        head hash
 * @param {string} fileName    changelog file name
 * @param {string} baseOwner   PR base owner
 * @param {string} baseName    PR base name
 * @return {Object.<string, string>} Paths to projects that have files changed in a PR keyed by the project name.
 */
export const getTouchedProjectsRequiringChangelog = async (
	tmpRepoPath: string,
	base: string,
	head: string,
	fileName: string,
	baseOwner: string,
	baseName: string
) => {
	const allProjectPaths = await getAllProjectPaths( tmpRepoPath );
	const changeloggerProjectsPaths = await getChangeloggerProjectPaths(
		tmpRepoPath,
		allProjectPaths
	);
	const touchedFilePaths = await getTouchedFilePaths(
		tmpRepoPath,
		base,
		head,
		fileName,
		baseOwner,
		baseName
	);

	return getTouchedChangeloggerProjectsPathsMappedToProjects(
		touchedFilePaths,
		changeloggerProjectsPaths
	);
};
