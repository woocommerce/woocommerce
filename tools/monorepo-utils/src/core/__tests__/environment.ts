/**
 * Internal dependencies
 */
import { isGithubCI } from '../environment';

describe( 'isGithubCI', () => {
	// Store the original environment variables so we can restore them.
	const originalEnv = process.env;

	beforeEach( () => {
		delete process.env.CI;
		delete process.env.GITHUB_ACTIONS;
	} );

	it( 'should return true if CI is set', () => {
		process.env.CI = 'true';
		expect( isGithubCI() ).toBe( true );
	} );

	it( 'should return false if CI is not set', () => {
		expect( isGithubCI() ).toBe( false );
	} );

	it( 'should return true if GITHUB_ACTIONS is set', () => {
		process.env.GITHUB_ACTIONS = 'true';
		expect( isGithubCI() ).toBe( true );
	} );

	it( 'should return false if GITHUB_ACTIONS is not set', () => {
		expect( isGithubCI() ).toBe( false );
	} );

	// Once we've completed all of the tests we need to set the environment
	// back to the way it was before we started. This makes sure we don't
	// leak anything to any other tests.
	afterAll( () => {
		process.env = originalEnv;
	} );
} );
