/**
 * Internal dependencies
 */
import { isGithubCI } from '../environment';

describe( 'isGithubCI', () => {
	it( 'should return true if CI is set', () => {
		process.env.CI = 'true';
		expect( isGithubCI() ).toBe( true );
	} );

	it( 'should return false if CI is not set', () => {
		delete process.env.CI;
		expect( isGithubCI() ).toBe( false );
	} );

	it( 'should return true if GITHUB_ACTIONS is set', () => {
		process.env.GITHUB_ACTIONS = 'true';
		expect( isGithubCI() ).toBe( true );
	} );

	it( 'should return false if GITHUB_ACTIONS is not set', () => {
		delete process.env.GITHUB_ACTIONS;
		expect( isGithubCI() ).toBe( false );
	} );

	afterAll( () => {
		delete process.env.CI;
		delete process.env.GITHUB_ACTIONS;
	} );
} );
