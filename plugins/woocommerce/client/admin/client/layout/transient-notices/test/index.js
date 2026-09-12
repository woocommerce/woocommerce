/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';
import { optionsStore, userStore, useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { TransientNotices } from '..';

const QUEUE_OPTION = 'woocommerce_admin_transient_notices_queue';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useDispatch: jest.fn(),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

useDispatch.mockReturnValue( {
	removeNotice: jest.fn(),
	createNotice: jest.fn(),
	updateOptions: jest.fn(),
} );

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUser: jest.fn().mockReturnValue( {
		currentUserCan: () => true,
	} ),
} ) );

jest.mock( '@woocommerce/admin-layout', () => {
	const originalModule = jest.requireActual( '@woocommerce/admin-layout' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		WooFooterItem: jest.fn( ( { children } ) => {
			return <div>{ children }</div>;
		} ),
	};
} );

jest.mock( '../snackbar/list', () =>
	jest.fn( ( { notices } ) => {
		return notices.map( ( notice ) => (
			<div key={ notice.title }>{ notice.title }</div>
		) );
	} )
);

describe( 'TransientNotices', () => {
	beforeEach( () => {
		useDispatch.mockReturnValue( {
			removeNotice: jest.fn(),
			createNotice: jest.fn(),
			updateOptions: jest.fn(),
		} );
		useSelect.mockReturnValue( {} );
		useUser.mockReturnValue( {
			currentUserCan: () => true,
		} );
	} );

	it( 'combines both notices and notices2 together and passes them to snackbar list', () => {
		useSelect.mockReturnValue( {
			notices: [ { title: 'first' } ],
			notices2: [ { title: 'second' } ],
		} );
		const { queryByText } = render( <TransientNotices /> );
		expect( queryByText( 'first' ) ).toBeInTheDocument();
		expect( queryByText( 'second' ) ).toBeInTheDocument();
	} );

	it( 'should default notices2 to empty array if undefined', () => {
		useSelect.mockReturnValue( {
			notices: [ { title: 'first' } ],
			notices2: undefined,
		} );
		const { queryByText } = render( <TransientNotices /> );
		expect( queryByText( 'first' ) ).toBeInTheDocument();
		expect( queryByText( 'second' ) ).not.toBeInTheDocument();
	} );

	it( 'should create notices from the queued notices', () => {
		useSelect.mockReturnValue( {
			noticesQueue: [
				{
					id: 'test-queued-notice',
					status: 'success',
					content: 'Test message',
				},
			],
		} );
		const createNotice = jest.fn();
		useDispatch.mockReturnValue( {
			createNotice,
		} );

		render( <TransientNotices /> );
		expect( createNotice ).toHaveBeenCalledWith(
			'success',
			'Test message',
			expect.anything()
		);
	} );

	it( 'should only show user specific notices', () => {
		useSelect.mockReturnValue( {
			currentUser: {
				id: 1,
			},
			noticesQueue: [
				{
					id: 'user-specific-notice',
					status: 'success',
					content: 'User specific message',
					user_id: 1,
				},
				{
					id: 'different-user-notice',
					status: 'success',
					content: 'Should not be shown',
					user_id: 2,
				},
			],
		} );
		const createNotice = jest.fn();
		useDispatch.mockReturnValue( {
			createNotice,
		} );

		render( <TransientNotices /> );
		expect( createNotice ).toHaveBeenCalledTimes( 1 );
		expect( createNotice ).toHaveBeenCalledWith(
			'success',
			'User specific message',
			expect.anything()
		);
	} );

	it.each( [
		{
			description: 'does not load the protected queue for a limited user',
			capabilities: [],
			expectedOptionCalls: [],
		},
		{
			description: 'loads the protected queue for a WooCommerce manager',
			capabilities: [ 'manage_woocommerce' ],
			expectedOptionCalls: [ [ QUEUE_OPTION ] ],
		},
		{
			description: 'loads the protected queue for an order manager',
			capabilities: [ 'edit_others_shop_orders' ],
			expectedOptionCalls: [ [ QUEUE_OPTION ] ],
		},
	] )( '$description', ( { capabilities, expectedOptionCalls } ) => {
		const getOption = jest.fn().mockReturnValue( {} );
		const select = jest.fn( ( store ) => {
			if ( store === optionsStore ) {
				return { getOption };
			}

			if ( store === userStore ) {
				return {
					getCurrentUser: jest.fn().mockReturnValue( { id: 1 } ),
				};
			}

			if ( store === 'core/notices' ) {
				return {
					getNotices: jest
						.fn()
						.mockReturnValue( [ { title: 'Local notice' } ] ),
				};
			}

			if ( store === 'core/notices2' ) {
				return {
					getNotices: jest
						.fn()
						.mockReturnValue( [ { title: 'Local notice 2' } ] ),
				};
			}

			return {};
		} );

		useUser.mockReturnValue( {
			currentUserCan: ( capability ) =>
				capabilities.includes( capability ),
		} );
		useSelect.mockImplementation( ( mapSelect ) => mapSelect( select ) );

		const { queryByText } = render( <TransientNotices /> );

		expect( getOption.mock.calls ).toEqual( expectedOptionCalls );
		expect( queryByText( 'Local notice' ) ).toBeInTheDocument();
		expect( queryByText( 'Local notice 2' ) ).toBeInTheDocument();
	} );
} );
