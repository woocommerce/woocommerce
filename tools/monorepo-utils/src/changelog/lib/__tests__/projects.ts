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
	it( 'should provide a list of all projects supplied by pnpm-workspace.yml', () => {
		const tmpRepoPath = path.join( __dirname, 'test-repo' );
		const projects = getAllProjects( tmpRepoPath, sampleWorkspaceYaml );
		console.log( tmpRepoPath );
		console.log( projects );
		expect( projects ).toEqual( [] );
	} );
} );
