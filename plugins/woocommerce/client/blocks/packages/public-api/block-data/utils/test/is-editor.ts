/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { getPath } from '@wordpress/url';

jest.mock( '@wordpress/data', () => ( { select: jest.fn() } ) );
jest.mock( '@wordpress/url', () => ( { getPath: jest.fn() } ) );

// tests/js/config/global-mocks.js mocks isEditor to always return false, so use
// the real implementation here.
const { isEditor } =
	jest.requireActual< typeof import('../is-editor') >( '../is-editor' );

const mockGetPath = getPath as jest.Mock;
const mockSelect = select as jest.Mock;

describe( 'isEditor', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockGetPath.mockReturnValue( '/' );
		mockSelect.mockReturnValue( undefined );
	} );

	it( 'returns true on the Site Editor screen', () => {
		mockGetPath.mockReturnValue( '/wp-admin/site-editor.php' );

		expect( isEditor() ).toBe( true );
	} );

	it( 'returns true on the post editor screen', () => {
		mockGetPath.mockReturnValue( '/wp-admin/post.php' );

		expect( isEditor() ).toBe( true );
	} );

	it( 'returns true in a block editor that has a post loaded for editing', () => {
		mockGetPath.mockReturnValue( '/wp-admin/post-new.php' );
		mockSelect.mockReturnValue( { getCurrentPostId: () => 42 } );

		expect( isEditor() ).toBe( true );
	} );

	it( 'returns false on the storefront when a plugin registered core/editor without editing a post', () => {
		mockGetPath.mockReturnValue( '/cart/' );
		mockSelect.mockReturnValue( { getCurrentPostId: () => null } );

		expect( isEditor() ).toBe( false );
	} );

	it( 'returns false on the storefront when core/editor is not registered', () => {
		mockGetPath.mockReturnValue( '/cart/' );
		mockSelect.mockReturnValue( undefined );

		expect( isEditor() ).toBe( false );
	} );
} );
