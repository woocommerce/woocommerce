/**
 * External dependencies
 */
import type { WCUser } from '@woocommerce/data';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { renderCustomerEffortScoreTracks } from '../shared';

const mockRender = jest.fn();

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	createRoot: jest.fn( () => ( {
		render: mockRender,
	} ) ),
} ) );

jest.mock( '@woocommerce/customer-effort-score', () => ( {
	CustomerEffortScoreTracksContainer: jest.fn( () => null ),
} ) );

const createUser = (
	capabilities: Record< string, boolean > = {},
	isSuperAdmin = false
) =>
	( {
		capabilities,
		is_super_admin: isSuperAdmin,
	} ) as WCUser;

describe( 'renderCustomerEffortScoreTracks', () => {
	let root = document.createElement( 'div' );

	beforeEach( () => {
		jest.clearAllMocks();
		root = document.createElement( 'div' );
	} );

	it( 'does not mount the queue container for a user without access to the options endpoint', () => {
		renderCustomerEffortScoreTracks(
			root,
			createUser( { manage_product: true } )
		);

		expect( createRoot ).not.toHaveBeenCalled();
		expect( mockRender ).not.toHaveBeenCalled();
	} );

	it.each( [ 'manage_woocommerce', 'edit_others_shop_orders' ] )(
		'mounts the queue container for a user with %s',
		( capability ) => {
			renderCustomerEffortScoreTracks(
				root,
				createUser( { [ capability ]: true } )
			);

			expect( createRoot ).toHaveBeenCalledTimes( 1 );
			expect( mockRender ).toHaveBeenCalledTimes( 1 );
		}
	);

	it( 'mounts the queue container for a super admin', () => {
		renderCustomerEffortScoreTracks( root, createUser( {}, true ) );

		expect( createRoot ).toHaveBeenCalledTimes( 1 );
		expect( mockRender ).toHaveBeenCalledTimes( 1 );
	} );
} );
