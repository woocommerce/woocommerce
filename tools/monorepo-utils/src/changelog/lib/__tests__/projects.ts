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
	getTouchedChangeloggerProjectsMapped,
} from '../projects';
import exp from 'constants';

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

	it( 'getTouchedChangeloggerProjectsMapped should combine touched and changelogger projects and return a list that is a subset of both', async () => {
		const touchedFiles = [
			'folder-with-lots-of-projects/project-b/src/index.js',
			'projects/very-cool-project/src/index.js',
		];
		const changeLoggerProjects = [
			'folder-with-lots-of-projects/project-b',
			'folder-with-lots-of-projects/project-a',
			'projects/very-cool-project',
		];
		const intersectedProjects = getTouchedChangeloggerProjectsMapped(
			touchedFiles,
			changeLoggerProjects
		);

		expect( intersectedProjects ).toHaveLength( 2 );
		const intersectedProjectsPaths = intersectedProjects.map(
			( i ) => i.path
		);
		expect( intersectedProjectsPaths ).toContain(
			'folder-with-lots-of-projects/project-b'
		);
		expect( intersectedProjectsPaths ).toContain(
			'projects/very-cool-project'
		);
	} );

	it( 'getTouchedChangeloggerProjectsMapped should map plugins and js packages to the correct name', async () => {
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
		const mappedTouchedProjects = getTouchedChangeloggerProjectsMapped(
			touchedFiles,
			changeLoggerProjects
		);

		expect( mappedTouchedProjects ).toHaveLength( 4 );

		const woocommerce = mappedTouchedProjects.find(
			( p ) => p.project === 'woocommerce'
		);
		const betaTester = mappedTouchedProjects.find(
			( p ) => p.project === 'beta-tester'
		);
		const components = mappedTouchedProjects.find(
			( p ) => p.project === '@woocommerce/components'
		);
		const data = mappedTouchedProjects.find(
			( p ) => p.project === '@woocommerce/data'
		);

		expect( woocommerce ).toBeDefined();
		expect( betaTester ).toBeDefined();
		expect( components ).toBeDefined();
		expect( data ).toBeDefined();

		expect( woocommerce.path ).toBe( 'plugins/woocommerce' );
		expect( betaTester.path ).toBe( 'plugins/beta-tester' );
		expect( components.path ).toBe( 'packages/js/components' );
		expect( data.path ).toBe( 'packages/js/data' );
	} );

	it( 'getTouchedChangeloggerProjectsMapped should handle woocommerce-admin projects mapped to woocommerce core', async () => {
		const touchedFiles = [
			'plugins/beta-tester/src/index.js',
			'plugins/woocommerce-admin/src/index.js',
		];
		const changeLoggerProjects = [
			'plugins/woocommerce',
			'plugins/beta-tester',
		];
		const mappedTouchedProjects = getTouchedChangeloggerProjectsMapped(
			touchedFiles,
			changeLoggerProjects
		);

		expect( mappedTouchedProjects ).toHaveLength( 2 );

		const woocommerce = mappedTouchedProjects.find(
			( p ) => p.project === 'woocommerce'
		);
		const betaTester = mappedTouchedProjects.find(
			( p ) => p.project === 'beta-tester'
		);

		expect( woocommerce ).toBeDefined();
		expect( betaTester ).toBeDefined();

		expect( woocommerce.path ).toBe( 'plugins/woocommerce' );
		expect( betaTester.path ).toBe( 'plugins/beta-tester' );
	} );
} );
