/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import { getAllProjects } from '../projects';

const sampleWorkspaceYaml = `
packages:
    - 'folder-with-lots-of-projects/*'
    - 'projects/cool-project'
    - 'projects/very-cool-project'
    - 'interesting-project'
`;

describe( 'getAllProjects', () => {
	it( 'should provide a list of all projects supplied by pnpm-workspace.yml', async () => {
		const tmpRepoPath = path.join( __dirname, 'test-repo' );
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
