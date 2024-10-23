jest.mock( 'uuid', () => {
	return {
		v4: jest.fn( () => 1 ),
	};
} );

/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import {
	getAllProjectsPathsFromWorkspace,
	getChangeloggerProjectPaths,
	getTouchedChangeloggerProjectsPathsMappedToProjects,
} from '../projects';

const sampleWorkspaceYaml = `
packages:
    - 'folder-with-lots-of-projects/*'
    - 'projects/cool-project'
    - 'projects/very-cool-project'
    - 'interesting-project'
`;
const tmpRepoPath = path.join( __dirname, 'test-repo' );

describe( 'Changelog project functions', () => {
	it( 'getAllProjectsPathsFromWorkspace should provide a list of all projects supplied by pnpm-workspace.yml', async () => {
		const projects = await getAllProjectsPathsFromWorkspace(
			tmpRepoPath,
			sampleWorkspaceYaml
		);
		const expectedProjects = [
			'folder-with-lots-of-projects/project-b',
			'folder-with-lots-of-projects/project-a',
			'projects/cool-project',
			'projects/very-cool-project',
			'interesting-project',
		];

		expectedProjects.forEach( ( expectedProject ) => {
			expect( projects ).toContain( expectedProject );
		} );

		expect( projects ).toHaveLength( expectedProjects.length );
	} );

	it( 'getChangeloggerProjectPaths should provide a list of all projects that use Jetpack changelogger', async () => {
		const projects = await getAllProjectsPathsFromWorkspace(
			tmpRepoPath,
			sampleWorkspaceYaml
		);
		const changeloggerProjects = await getChangeloggerProjectPaths(
			tmpRepoPath,
			projects
		);

		const expectedChangeLoggerProjects = [
			'folder-with-lots-of-projects/project-b',
			'folder-with-lots-of-projects/project-a',
			'projects/very-cool-project',
		];

		expectedChangeLoggerProjects.forEach(
			( expectedChangeLoggerProject ) => {
				expect( changeloggerProjects ).toContain(
					expectedChangeLoggerProject
				);
			}
		);

		expect( changeloggerProjects ).toHaveLength(
			expectedChangeLoggerProjects.length
		);
	} );

	it( 'getTouchedChangeloggerProjectsPathsMappedToProjects should combine touched and changelogger projects and return a list that is a subset of both', async () => {
		const touchedFiles = [
			'folder-with-lots-of-projects/project-b/src/index.js',
			'projects/very-cool-project/src/index.js',
		];
		const changeLoggerProjects = [
			'folder-with-lots-of-projects/project-b',
			'folder-with-lots-of-projects/project-a',
			'projects/very-cool-project',
		];
		const intersectedProjects =
			getTouchedChangeloggerProjectsPathsMappedToProjects(
				touchedFiles,
				changeLoggerProjects
			);

		expect( intersectedProjects ).toMatchObject( {
			'folder-with-lots-of-projects/project-b':
				'folder-with-lots-of-projects/project-b',
			'projects/very-cool-project': 'projects/very-cool-project',
		} );
	} );

	it( 'getTouchedChangeloggerProjectsPathsMappedToProjects should map plugins and js packages to the correct name', async () => {
		const touchedFiles = [
			'plugins/beta-tester/src/index.js',
			'plugins/woocommerce/src/index.js',
			'packages/js/components/src/index.js',
			'packages/js/data/src/index.js',
		];
		const changeLoggerProjects = [
			'plugins/woocommerce',
			'plugins/beta-tester',
			'packages/js/data',
			'packages/js/components',
		];
		const intersectedProjects =
			getTouchedChangeloggerProjectsPathsMappedToProjects(
				touchedFiles,
				changeLoggerProjects
			);

		expect( intersectedProjects ).toMatchObject( {
			woocommerce: 'plugins/woocommerce',
			'beta-tester': 'plugins/beta-tester',
			'@woocommerce/components': 'packages/js/components',
			'@woocommerce/data': 'packages/js/data',
		} );
	} );
} );
