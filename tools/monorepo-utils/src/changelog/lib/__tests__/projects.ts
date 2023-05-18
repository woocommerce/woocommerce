/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import { getAllProjects, getChangeloggerProjects } from '../projects';

const sampleWorkspaceYaml = `
packages:
    - 'folder-with-lots-of-projects/*'
    - 'projects/cool-project'
    - 'projects/very-cool-project'
    - 'interesting-project'
`;
const tmpRepoPath = path.join( __dirname, 'test-repo' );

describe( 'getAllProjects', () => {
	it( 'should provide a list of all projects supplied by pnpm-workspace.yml', async () => {
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
} );

describe( 'getChangeloggerProjects', () => {
	it( 'should provide a list of all projects that use Jetpack changelogger', async () => {
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
} );
