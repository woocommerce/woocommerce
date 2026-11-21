/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { TransientNotices } from '..';

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
} );

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
} );
