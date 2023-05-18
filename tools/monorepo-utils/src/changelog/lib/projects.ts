/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';
import { glob } from 'glob';
import simpleGit from 'simple-git';

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

export const getChangeloggerProjects = async (
	tmpRepoPath: string,
	projects: Array< string >
) => {
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
			( composer[ 'require-dev' ] &&
				composer[ 'require-dev' ][ 'automattic/jetpack-changelogger' ] )
		);
	} );
};

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

export const intersectTouchedFilesWithChangeloggerProjects = (
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
			return project.replace( 'plugins/', '' );
		} else if ( project.includes( 'packages/js/' ) ) {
			return project.replace( 'packages/js/', '@woocommerce/' );
		}
		return project;
	} );
};

export const getTouchedProjectsRequiringChangelog = async (
	tmpRepoPath: string,
	base: string,
	head: string
) => {
	const workspaceYaml = fs.readFileSync(
		path.join( tmpRepoPath, 'pnpm-workspace.yaml' ),
		'utf8'
	);
	const projects = await getAllProjects( tmpRepoPath, workspaceYaml );
	const changeloggerProjects = await getChangeloggerProjects(
		tmpRepoPath,
		projects
	);
	const touchedFiles = await getTouchedFiles( tmpRepoPath, base, head );

	return intersectTouchedFilesWithChangeloggerProjects(
		touchedFiles,
		changeloggerProjects
	);
};
