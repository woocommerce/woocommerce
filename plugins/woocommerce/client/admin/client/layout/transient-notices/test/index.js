import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { TransientNotices } from '..';

vi.mock( '@wordpress/data', async () => {
	// Require the original module to not be mocked...
	const originalModule = await vi.importActual( '@wordpress/data' );

	return ( ( mock ) => ( { default: mock, ...mock } ) )( {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useDispatch: vi.fn(),
		useSelect: vi.fn().mockReturnValue( {} ),
	} );
} );

useDispatch.mockReturnValue( {
	removeNotice: vi.fn(),
	createNotice: vi.fn(),
} );

vi.mock( '@woocommerce/admin-layout', async () => {
	const originalModule = await vi.importActual( '@woocommerce/admin-layout' );

	return ( ( mock ) => ( { default: mock, ...mock } ) )( {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		WooFooterItem: vi.fn( ( { children } ) => {
			return <div>{ children }</div>;
		} ),
	} );
} );

vi.mock( '../snackbar/list', () => {
	const mock = vi.fn( ( { notices } ) => {
		return notices.map( ( notice ) => (
			<div key={ notice.title }>{ notice.title }</div>
		) );
	} );
	return { default: mock, ...mock };
} );

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
		const createNotice = vi.fn();
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
		const createNotice = vi.fn();
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
