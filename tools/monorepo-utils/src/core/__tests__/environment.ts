/**
 * Internal dependencies
 */
import { isGithubCI } from '../environment';

describe( 'isGithubCI', () => {
	it( 'should return true if GITHUB_ACTIONS is true', () => {
		process.env.GITHUB_ACTIONS = 'true';
		expect( isGithubCI() ).toBe( true );
	} );

	it( 'should return false if GITHUB_ACTIONS is false', () => {
		process.env.GITHUB_ACTIONS = 'false';
		expect( isGithubCI() ).toBe( false );
	} );

	it( 'should return false if GITHUB_ACTIONS is not set', () => {
		process.env.GITHUB_ACTIONS = undefined;
		expect( isGithubCI() ).toBe( false );
	} );

	afterAll( () => {
		delete process.env.GITHUB_ACTIONS;
	} );
} );
