/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import {
	getAllProjects,
	getChangeloggerProjects,
	intersectTouchedFilesWithChangeloggerProjects,
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
	it( 'getAllProjects should provide a list of all projects supplied by pnpm-workspace.yml', async () => {
		const projects = await getAllProjects(
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

	it( 'getChangeloggerProjects should provide a list of all projects that use Jetpack changelogger', async () => {
		const projects = await getAllProjects(
			tmpRepoPath,
			sampleWorkspaceYaml
		);
		const changeloggerProjects = await getChangeloggerProjects(
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
				expect( projects ).toContain( expectedChangeLoggerProject );
			}
		);

		expect( changeloggerProjects ).toHaveLength(
			expectedChangeLoggerProjects.length
		);
	} );

	it( 'intersectTouchedFilesWithChangeloggerProjects should combine touched and changelogger projects and return a list that is a subset of both', async () => {
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
			intersectTouchedFilesWithChangeloggerProjects(
				touchedFiles,
				changeLoggerProjects
			);

		expect( intersectedProjects ).toHaveLength( 2 );
		expect( intersectedProjects ).toContain(
			'folder-with-lots-of-projects/project-b'
		);
		expect( intersectedProjects ).toContain( 'projects/very-cool-project' );
	} );

	it( 'intersectTouchedFilesWithChangeloggerProjects should map plugins and js packages to the correct name', async () => {
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
			intersectTouchedFilesWithChangeloggerProjects(
				touchedFiles,
				changeLoggerProjects
			);

		expect( intersectedProjects ).toHaveLength( 4 );
		expect( intersectedProjects ).toContain( 'woocommerce' );
		expect( intersectedProjects ).toContain( 'beta-tester' );
		expect( intersectedProjects ).toContain( '@woocommerce/components' );
		expect( intersectedProjects ).toContain( '@woocommerce/data' );
	} );

	it( 'intersectTouchedFilesWithChangeloggerProjects should handle woocommerce-admin projects mapped to woocommerce core', async () => {
		const touchedFiles = [
			'plugins/beta-tester/src/index.js',
			'plugins/woocommerce-admin/src/index.js',
		];
		const changeLoggerProjects = [
			'plugins/woocommerce',
			'plugins/beta-tester',
		];
		const intersectedProjects =
			intersectTouchedFilesWithChangeloggerProjects(
				touchedFiles,
				changeLoggerProjects
			);

		expect( intersectedProjects ).toHaveLength( 2 );
		expect( intersectedProjects ).toContain( 'woocommerce' );
		expect( intersectedProjects ).toContain( 'beta-tester' );
	} );
} );
