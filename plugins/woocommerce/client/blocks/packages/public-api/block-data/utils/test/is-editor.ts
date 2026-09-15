/**
 * External dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { isEditor } from '../is-editor';

jest.unmock( '@woocommerce/block-data/utils/is-editor' );

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn(),
} ) );

describe( 'isEditor', () => {
	const originalUrl = window.location.href;
	const mockSelect = select as jest.Mock;

	beforeEach( () => {
		window.history.replaceState( {}, '', '/cart/' );
		mockSelect.mockReturnValue( undefined );
	} );

	afterEach( () => {
		window.history.replaceState( {}, '', originalUrl );
		jest.clearAllMocks();
	} );

	it( 'returns false on the frontend without the editor store', () => {
		expect( isEditor() ).toBe( false );
	} );

	it( 'returns false when the editor store is registered without an edited post', () => {
		mockSelect.mockReturnValue( {
			getCurrentPostType: () => undefined,
		} );
		expect( isEditor() ).toBe( false );
	} );

	it( 'returns false when the editor selector is unavailable', () => {
		mockSelect.mockReturnValue( {} );
		expect( isEditor() ).toBe( false );
	} );

	it.each( [ 'post', 'page', 'wp_template', 'wp_template_part' ] )(
		'returns true when editing a %s',
		( postType ) => {
			mockSelect.mockReturnValue( {
				getCurrentPostType: () => postType,
			} );
			expect( isEditor() ).toBe( true );
		}
	);

	it( 'returns true when creating a new post', () => {
		window.history.replaceState( {}, '', '/wp-admin/post-new.php' );
		mockSelect.mockReturnValue( {
			getCurrentPostType: () => 'post',
		} );
		expect( isEditor() ).toBe( true );
	} );

	it.each( [ 'post.php?post=1&action=edit', 'site-editor.php' ] )(
		'returns true on %s before the editor store is registered',
		( path ) => {
			window.history.replaceState( {}, '', `/wp-admin/${ path }` );
			expect( isEditor() ).toBe( true );
		}
	);
} );
