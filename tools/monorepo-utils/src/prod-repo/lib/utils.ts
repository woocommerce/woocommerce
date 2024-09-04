/**
 * External dependencies
 */
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { tmpdir } from 'node:os';
import { v4 } from 'uuid';
import fs from 'fs-extra';

/**
 * Internal dependencies
 */
import { loadPackage } from '../../ci-jobs/lib/package-file';

export function getAllProjects() {
	const projects = JSON.parse(
		spawnSync(
			'pnpm',
			[
				'-r',
				'list',
				'--only-projects',
				'--json',
			],
			{ encoding: 'utf-8' }
		).output.join( '\n' )
	);
	return projects.map( project => project.name );
}

export function getProjectPathFromFilter( filter ) {
	let projectPath = null;
	try {
		const projects = JSON.parse(
			spawnSync(
				'pnpm',
				[
					'-r',
					'list',
					'--only-projects',
					'--json',
					'--filter',
					filter,
				],
				{ encoding: 'utf-8' }
			).output.join( '\n' )
		);
		if ( projects.length === 1 ) {
			projectPath = projects[ 0 ].path;
		}
	} catch ( e ) {}
	return projectPath;
}

export function getProdConfig( projectPath ) {
	const projectJsonPath = path.join( projectPath, 'package.json' );

	const project = loadPackage( projectJsonPath );

	return project.config.prod ?? {};
}

export function getTargetRepoFromConfig( projectPath ) {
	const prodConfig = getProdConfig( projectPath );
	return prodConfig.repo;
}

export function getTargetBranchFromConfig( projectPath ) {
	const prodConfig = getProdConfig( projectPath );
	return prodConfig.branch;
}

export function runBuildCommand( filter ) {
	const projectPath = getProjectPathFromFilter( filter );
	const prodConfig = getProdConfig( projectPath );
	const buildCommand = prodConfig[ 'build-command' ];
	spawnSync( 'pnpm', [ '--filter', filter, buildCommand ], {
		stdio: 'inherit',
	} );
}

export function getArchiveFromConfig( projectPath ) {
	const prodConfig = getProdConfig( projectPath );
	return path.join( projectPath, prodConfig.archive );
}

export async function extractZipTo( zipFile, targetPath ) {
	// @todo replace with npm package to handle this
	const tmpDir = path.join( tmpdir(), 'monorepo-utils-tmp', v4() );
	fs.mkdirSync( tmpDir, { recursive: true } );
	spawnSync( 'unzip', [ zipFile, '-d', tmpDir ] );
	const dirs = fs
		.readdirSync( tmpDir, { withFileTypes: true } )
		.filter( ( item ) => item.isDirectory() )
		.map( ( item ) => item.name );
	const extractDir = path.join( tmpDir, dirs[ 0 ] );
	const files = fs
		.readdirSync( extractDir, { withFileTypes: true } )
		.map( ( item ) => item.name );
	for ( let i = 0; i < files.length; i++ ) {
		await fs.move(
			path.join( extractDir, files[ i ] ),
			path.join( targetPath, files[ i ] ),
			{ overwrite: true }
		);
	}
}